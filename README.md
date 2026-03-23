# respect/fluent

Build fluent interfaces from class namespaces. PHP 8.5+, zero dependencies.

Fluent maps method calls to class instances. You define classes in a namespace,
extend `FluentBuilder`, and get a chainable API where each call resolves a
class name, instantiates it, and accumulates it immutably.

```php
$stack = Middleware::cors('*')
    ->rateLimit(100)
    ->auth('bearer')
    ->jsonBody();

$stack->getNodes(); // [Cors('*'), RateLimit(100), Auth('bearer'), JsonBody()]
```

Middlewares, validators, processors. Anything that has potential for chain
composability could leverage respect/fluent.

## Installation

```bash
composer require respect/fluent
```

## Quick Start

### 1. Choose a namespace and interface

Fluent uses classes from one or more namespaces, and they all must share
a single interface.

For example:

```php
namespace App\Middleware;

interface Middleware
{
    public function process(Request $request, Handler $next): Response;
}

final readonly class Cors implements Middleware
{
    public function __construct(private string $origin = '*') {}
    public function process(Request $request, Handler $next): Response { /* ... */ }
}

final readonly class RateLimit implements Middleware
{
    public function __construct(private int $maxRequests = 60) {}
    public function process(Request $request, Handler $next): Response { /* ... */ }
}
// etc...
```

### 2. Extend FluentBuilder

The `__call` method, immutable accumulation, and `withNamespace` support are
inherited. You only add domain logic:

```php
namespace App;

use Respect\Fluent\Builders\Append;
use Respect\Fluent\Factories\NamespaceLookup;
use Respect\Fluent\Resolvers\Ucfirst;
use App\Middleware\Middleware;

final readonly class MiddlewareStack extends Append
{
    public function __construct(Middleware ...$layers)
    {
        parent::__construct(
            new NamespaceLookup(
                new Ucfirst(),     // fooBar -> new FooBar
                Middleware::class, // FooBar implements Middleware
                'App\\Middleware'  // App\Middleware\FooBar
            ),
            ...$layers,
        );
    }

    // handle domain logic
    public function handle(Request $request, Handler $handler): Response
    {
        foreach ($this->getNodes() as $middleware) {
            // do something
        }
        return $handler($request);
    }
}
```

That's it. `MiddlewareStack::cors()->auth('bearer')->jsonBody()` then
builds the layers for you.

### 3. Add composition if you want

Prefix composition lets `optionalAuth()` create `Optional(Auth())`. You're
not limited to `Optional` cases, you can design nesting as much as you want.

Annotate wrapper classes with `#[Composable]` and use `ComposingLookup`:

```php
namespace App\Middleware;

use Respect\Fluent\Attributes\Composable;

#[Composable('optional')]
final readonly class Optional implements Middleware
{
    public function __construct(private Middleware $inner) {}

    public function process(Request $request, Handler $next): Response
    {
        // Skip the middleware if a condition is met
        return $this->shouldSkip($request)
            ? $next($request)
            : $this->inner->process($request, $next);
    }
}
```

Update the constructor to use `ComposingLookup`:

```php
use Respect\Fluent\Factories\ComposingLookup;
use Respect\Fluent\Attributes\ComposableAttributes;

$flat = new NamespaceLookup(new Ucfirst(), Middleware::class, 'App\\Middleware');
parent::__construct(
    new ComposingLookup($flat, new ComposableAttributes($flat)),
    ...$layers,
);
```

Now `MiddlewareStack::optionalAuth('bearer')` creates `Optional(Auth('bearer'))`.

### 4. Add custom namespaces

Users can extend your middleware stack with their own classes.
`withNamespace` is inherited from `FluentBuilder`:

```php
$stack = MiddlewareStack::cors();
$extended = $stack->withNamespace('MyApp\\CustomMiddleware');
$extended->logging();  // Finds MyApp\CustomMiddleware\Logging
```

## How It Works

Fluent has three layers:

- **Resolvers** transform method names before lookup (e.g., `'email'` →
  `'Email'`, or `'notEmail'` → wrapper `'Not'` + inner `'Email'`).
- **Factories** search namespaces for the resolved class name and instantiate
  it.
- **Builders** (`Append`, `Prepend`) chain factory calls immutably via `__call`.

Resolved classes are called **nodes** because consumer libraries (like
Respect/Validation) often arrange them into tree structures.

A **FluentNode** carries the resolution state between resolvers and factories:
a name, constructor arguments, and an optional wrapper.

```
                         +-----------+
  'notEmail' -------->   |  Resolver |   ------>  FluentNode('Email', wrapper: FluentNode('Not'))
                         +-----------+
                              |
                              v
                         +-----------+
  FluentNode ----------->  |  Factory  |   ------>  Not(Email())
                         +-----------+
```

**NamespaceLookup vs ComposingLookup:** use `NamespaceLookup` for simple
name→class mapping. Add `ComposingLookup` when you need prefix composition
like `notEmail()` → `Not(Email())`. `ComposingLookup` supports recursive
unwrapping, so `notNullOrEmail()` → `Not(NullOr(Email()))` works too.

## API Reference

### Builders

Abstract base `FluentBuilder` provides `__call`, `__callStatic`, `getNodes()`,
`withNamespace()`, and the abstract `attach()` method. Two concrete builders:

**`Append`** — each `attach()` appends nodes to the end:

```php
$builder = new Append($factory);
$chain = $builder->cors()->auth('bearer');
$chain->getNodes();                      // [Cors(), Auth('bearer')]
$chain->attach($manualNode);             // add pre-built objects
$chain->withNamespace('Extra\\Ns');      // prepend a search namespace
```

**`Prepend`** — each `attach()` prepends nodes to the front:

```php
$builder = new Prepend($factory);
$chain = $builder->cors()->auth('bearer');
$chain->getNodes();                      // [Auth('bearer'), Cors()]
```

Both are `readonly` and not `final` — extend them and add your domain methods.
`__callStatic` calls `new static()` by default; override it if your subclass
needs a different way to obtain a default instance.

### FluentFactory

Interface implemented by both factories:

```php
interface FluentFactory
{
    public function create(string $name, array $arguments = []): object;
    public function withNamespace(string $namespace): static;
}
```

#### NamespaceLookup

The primary factory. Searches namespaces in order for a matching class.

```php
$lookup = new NamespaceLookup(
    new Ucfirst(),             // resolver: 'email' → 'Email'
    MyInterface::class,        // optional type validation
    'App\\Handlers',           // primary namespace
    'App\\Handlers\\Fallback', // fallback namespace
);

$lookup->create('email', ['strict' => true]);  // new App\Handlers\Email(strict: true)
$lookup->resolve('email');                      // ReflectionClass (without instantiating)
```

Immutable builders: `withNamespace()` prepends a namespace, `withNodeType()`
adds type validation. Both return new instances.

#### ComposingLookup

Wraps a `NamespaceLookup` + `FluentResolver` to handle prefix composition. When
the resolver produces a wrapper FluentNode, ComposingLookup creates the inner
instance first, then wraps it. Supports recursive unwrapping for nested
wrappers.

```php
$nested = new ComposingLookup($lookup, new ComposableAttributes($lookup));
$nested->create('notEmail');  // Not(Email())
```

### FluentResolver

Interface for name transformers:

```php
interface FluentResolver
{
    public function resolve(FluentNode $nodeSpec): FluentNode;
}
```

#### Ucfirst

Capitalizes the first letter: `'email'` → `'Email'`.

#### Suffix

Strips a prefix and appends a suffix: `Suffix('of', 'Handler')` turns
`'ofArray'` → `'ArrayHandler'`.

#### Composable (attribute)

A PHP attribute that marks a class as a prefix wrapper for composition:

```php
#[Composable('not')]
final readonly class Not implements Validator
{
    public function __construct(private Validator $validator) {}
}
```

#### ComposableAttributes

Discovers `#[Composable]` attributes at runtime and decomposes prefixed names:
`'notEmail'` → `FluentNode('Email', wrapper: FluentNode('Not'))`.

```php
$resolver = new ComposableAttributes($lookup);
```

Discovery results are cached for performance.

Attribute properties:

| Property | Type | Purpose |
|---|---|---|
| `prefix` | `string` | Registers this class as a composition prefix |
| `prefixParameter` | `bool` | First argument goes to the wrapper |
| `optIn` | `bool` | Only compose with prefixes listed in `with` |
| `without` | `array` | Prefixes this class should not be composed with |
| `with` | `array` | Prefixes this class should be composed with |

#### ComposableMap

Optimized `Composable` subclass using a pre-built map instead of runtime
discovery. Use with code generators like respect/codegen:

```php
$resolver = new ComposableMap(
    composable: ['not' => true, 'nullOr' => true],
    composableWithArgument: ['key' => true],
);
```

### FluentNode

Readonly data class carrying resolution state:

```php
new FluentNode(
    name: 'Email',
    arguments: ['strict' => true],
    wrapper: new FluentNode('Not'),  // optional
);
```

### Exceptions

All exceptions implement `FluentException` (a `Throwable` marker interface),
so consumers can catch all Fluent errors with a single type:

```php
use Respect\Fluent\Exceptions\FluentException;

try {
    $factory->create('nonExistent');
} catch (FluentException $e) {
    // ...
}
```

| Exception | Parent | Thrown when |
|---|---|---|
| `CouldNotResolve` | `InvalidArgumentException` | Name not found in any registered namespace |
| `CouldNotCreate` | `InvalidArgumentException` | Instantiation failed or type validation failed |

Both extend `InvalidArgumentException` for backwards compatibility.
