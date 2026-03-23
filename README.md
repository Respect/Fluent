# Respect\Fluent

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

Middlewares, validators, processors: anything that composes well as a chain
can leverage Respect/Fluent.

## Installation

```bash
composer require respect/fluent
```

## Quick Start

### 1. Choose a namespace and interface

Fluent discovers classes from one or more namespaces. Giving them a shared
interface lets your builder enforce type safety and expose domain methods.

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

The `#[FluentNamespace]` attribute declares where your classes live and how to
resolve them. The builder inherits `__call`, immutable accumulation, and
`withNamespace` support, you only add domain logic:

```php
namespace App;

use Respect\Fluent\Attributes\FluentNamespace;
use Respect\Fluent\Builders\Append;
use Respect\Fluent\Factories\NamespaceLookup;
use Respect\Fluent\Resolvers\Ucfirst;
use App\Middleware\Middleware;

#[FluentNamespace(new NamespaceLookup(new Ucfirst(), Middleware::class, 'App\\Middleware'))]
final readonly class MiddlewareStack extends Append
{
    public function __construct(Middleware ...$layers)
    {
        parent::__construct(static::factoryFromAttribute(), ...$layers);
    }

    /** @return array<int, Middleware> */
    public function layers(): array
    {
        return $this->getNodes();
    }
}
```

The attribute carries the full factory configuration: the resolver (`Ucfirst`),
optional type constraint (`Middleware::class`), and namespace to search. The
inherited `factoryFromAttribute()` reads it at runtime so there's a single
source of truth.

Now `MiddlewareStack::cors()->auth('bearer')->jsonBody()` builds the
layers for you.

### 3. Add composition if you want

Prefix composition lets `optionalAuth()` create `Optional(Auth())`. You're
not limited to `Optional` cases, you can design nesting as deep as you want.

Annotate wrapper classes with `#[Composable]`:

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

Then switch the attribute to use `ComposingLookup`, it automatically discovers
`#[Composable]` prefixes from the same namespace:

```php
use Respect\Fluent\Factories\ComposingLookup;

#[FluentNamespace(new ComposingLookup(
    new NamespaceLookup(new Ucfirst(), Middleware::class, 'App\\Middleware'),
))]
final readonly class MiddlewareStack extends Append { /* ... */ }
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
name-to-class mapping. Wrap it with `ComposingLookup` when you need prefix
composition like `notEmail()` → `Not(Email())`. `ComposingLookup` supports
recursive unwrapping, so `notNullOrEmail()` → `Not(NullOr(Email()))` works too.

## API Reference

### FluentNamespace (attribute)

Declares the factory configuration for a builder class. Both the runtime
(`factoryFromAttribute()`) and static analysis (FluentAnalysis) read from this
single source of truth:

```php
use Respect\Fluent\Attributes\FluentNamespace;

// Simple lookup
#[FluentNamespace(new NamespaceLookup(new Ucfirst(), null, 'App\\Handlers'))]

// With type validation
#[FluentNamespace(new NamespaceLookup(new Ucfirst(), Handler::class, 'App\\Handlers'))]

// With prefix composition
#[FluentNamespace(new ComposingLookup(
    new NamespaceLookup(new Ucfirst(), Validator::class, 'App\\Validators'),
))]
```

### Builders

Abstract base `FluentBuilder` provides `__call`, `__callStatic`, `getNodes()`,
`withNamespace()`, `factoryFromAttribute()`, and the abstract `attach()` method.
Two concrete builders:

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

Both are `readonly` and not `final`, extend them and add your domain methods.
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

The `$resolver` and `$namespaces` properties are `public private(set)`, you
can read them (useful for tooling like FluentAnalysis) but not reassign them.

Immutable builders: `withNamespace()` prepends a namespace, `withNodeType()`
adds type validation. Both return new instances.

#### ComposingLookup

Wraps a `NamespaceLookup` to handle prefix composition. When the resolver
produces a wrapper FluentNode, ComposingLookup creates the inner instance
first, then wraps it. Supports recursive unwrapping for nested wrappers.

```php
$nested = new ComposingLookup($lookup);  // defaults to ComposableAttributes
$nested->create('notEmail');             // Not(Email())
```

You can pass a custom resolver as the second argument if you don't want
automatic `#[Composable]` attribute discovery:

```php
$nested = new ComposingLookup($lookup, new ComposableMap(
    composable: ['not' => true],
));
```

### FluentResolver

Interface for name transformers. Each resolver can `resolve` a method name
into a class name, and `unresolve` it back:

```php
interface FluentResolver
{
    public function resolve(FluentNode $nodeSpec): FluentNode;
    public function unresolve(FluentNode $nodeSpec): FluentNode;
}
```

The `unresolve` method is the inverse of `resolve`: it converts a class name
back to the method name that would produce it. This is used by FluentAnalysis
to derive method maps from discovered classes.

#### Ucfirst

Capitalizes the first letter: `'email'` → `'Email'`.
Unresolve does the opposite: `'Email'` → `'email'`.

#### Suffix

Strips a prefix and appends a suffix: `Suffix('of', 'Handler')` turns
`'ofArray'` → `'ArrayHandler'`.
Unresolve reverses it: `'ArrayHandler'` → `'ofArray'`.

#### Composable (attribute)

A PHP attribute that marks a class as a prefix wrapper for composition.
Constraints (`without`, `with`, `optIn`) are enforced at resolve time:

```php
#[Composable('not', without: ['not'])]  // prevents notNot()
final readonly class Not implements Validator
{
    public function __construct(private Validator $validator) {}
}
```

Attribute properties:

| Property          | Type     | Purpose                                         |
|-------------------|----------|-------------------------------------------------|
| `prefix`          | `string` | Registers this class as a composition prefix    |
| `prefixParameter` | `bool`   | First argument goes to the wrapper              |
| `optIn`           | `bool`   | Only compose with prefixes listed in `with`     |
| `without`         | `array`  | Prefixes this class should not be composed with |
| `with`            | `array`  | Prefixes this class should be composed with     |

#### ComposableAttributes

Discovers `#[Composable]` attributes at runtime and decomposes prefixed names:
`'notEmail'` → `FluentNode('Email', wrapper: FluentNode('Not'))`.

```php
$resolver = new ComposableAttributes($lookup);
```

Caches prefix discoveries, suffix constraints, and negative lookups for
performance. Unresolve flattens wrapper structures back to flat names.

#### ComposableMap

Pre-built resolver using a compiled prefix map instead of runtime discovery.
Useful for code-generated setups where all prefixes are known ahead of time:

```php
$resolver = new ComposableMap(
    composable: ['not' => true, 'nullOr' => true],
    composableWithArgument: ['key' => true],
    forbidden: ['Not' => ['not' => true]],  // suffix => [prefix => true]
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
so you can catch all Fluent errors with a single type:

```php
use Respect\Fluent\Exceptions\FluentException;

try {
    $factory->create('nonExistent');
} catch (FluentException $e) {
    // ...
}
```

| Exception         | Parent                     | Thrown when                                    |
|-------------------|----------------------------|------------------------------------------------|
| `CouldNotResolve` | `InvalidArgumentException` | Name not found in any registered namespace     |
| `CouldNotCreate`  | `InvalidArgumentException` | Instantiation failed or type validation failed |

Both extend `InvalidArgumentException` for backwards compatibility.
