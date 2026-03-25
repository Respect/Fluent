<!--
SPDX-FileCopyrightText: (c) Respect Project Contributors
SPDX-License-Identifier: ISC
SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
-->
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

#[Composable(self::class)]
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
                         +----------+
  'notEmail' -------->   | Resolver |  ------>  FluentNode('Email', wrapper: FluentNode('Not'))
                         +----------+
                              |
                              v
                         +----------+
  FluentNode --------->  | Factory  |  ------>  Not(Email())
                         +----------+
```

**NamespaceLookup vs ComposingLookup:** use `NamespaceLookup` for simple
name-to-class mapping. Wrap it with `ComposingLookup` when you need prefix
composition like `notEmail()` → `Not(Email())`. `ComposingLookup` supports
recursive unwrapping, so `notNullOrEmail()` → `Not(NullOr(Email()))` works too.

## Assurance attributes

Node classes can declare what they assure about their input via `#[Assurance]`.
Assertion methods are marked with `#[AssuranceAssertion]`, and `#[AssuranceParameter]`
identifies specific parameters. Constructor parameters for composition use
`#[ComposableParameter]`.

This metadata is available at runtime through reflection and is also consumed
by tools like [FluentAnalysis](https://github.com/Respect/FluentAnalysis)
for static type narrowing.

```php
#[Assurance(type: 'int')]
final readonly class IntType implements Validator { /* ... */ }

final readonly class ValidatorBuilder extends Append
{
    #[AssuranceAssertion]
    public function assert(#[AssuranceParameter] mixed $input): void { /* ... */ }

    #[AssuranceAssertion]
    public function isValid(#[AssuranceParameter] mixed $input): bool { /* ... */ }
}
```

See `Assurance`, `AssuranceParameter`, `ComposableParameter`, and the enum
types in the [API reference](docs/api.md#assurance) for the full set of options.

## API Reference

See [docs/api.md](docs/api.md) for the complete API reference covering
attributes, builders, factories, resolvers, and exceptions.
