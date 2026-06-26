<!--
SPDX-FileCopyrightText: (c) Respect Project Contributors
SPDX-License-Identifier: ISC
SPDX-FileContributor: Alexandre Gomes Gaigalas <alganet@gmail.com>
-->
# API Reference

## Attributes

### FluentNamespace

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

### Composable

A PHP attribute that marks a class as a prefix wrapper for composition.
Constraints (`without`, `with`, `optIn`) are enforced at resolve time:

```php
#[Composable(self::class, without: [self::class])]  // prevents notNot()
final readonly class Not implements Validator
{
    public function __construct(private Validator $validator) {}
}
```

Parameters accept class references (`Not::class`, `self::class`). Consumers
resolve class-strings to prefix strings via their resolver (e.g. `lcfirst(short_name)`).

Properties:

| Property  | Type                 | Purpose                                                            |
|-----------|----------------------|--------------------------------------------------------------------|
| `prefix`  | `class-string|null`  | Registers this class as a composition prefix (null = not a prefix) |
| `optIn`   | `bool`               | Only compose with prefixes listed in `with`                        |
| `without` | `list<class-string>` | Prefix classes this class should not be composed with              |
| `with`    | `list<class-string>` | Prefix classes this class should be composed with                  |

Use `#[ComposableParameter]` on a constructor parameter to promote it to the
composed method signature (see ComposableParameter below).

### AssuranceAssertion

Marks a method as a type narrowing assertion. Applied to individual methods on
a builder class. Use `#[AssuranceParameter]` on the parameter that receives the
value being validated:

```php
final readonly class ValidatorBuilder extends Append
{
    #[AssuranceAssertion]
    public function assert(#[AssuranceParameter] mixed $input): void { /* throws on failure */ }

    #[AssuranceAssertion]
    public function isValid(#[AssuranceParameter] mixed $input): bool { /* returns true/false */ }
}
```

### AssuranceParameter

Selects **which** argument carries the assurance information — purely an index,
defaulting to the first parameter when absent. It does not itself imply any
particular derivation; `from:` decides how the selected argument maps to the
assured type. Contextual based on where it appears:

**On a constructor parameter** — selects which argument is the type source. The
example below pairs it with `from: TypeString` so the class-string argument
narrows to an instance of that class (replaces the old `parameter:` string
reference):

```php
#[Assurance(from: AssuranceFrom::TypeString, exact: true)]
final readonly class Instance implements Validator
{
    public function __construct(
        #[AssuranceParameter] private string $class,
    ) {}
}
```

**On an assertion method parameter** — identifies which parameter receives the
value being validated:

```php
#[AssuranceAssertion]
public function assert(#[AssuranceParameter] mixed $input): void { /* ... */ }
```

### ComposableParameter

Marks a constructor parameter as the prefix parameter for composition. The
runtime composition logic always promotes the *first* fluent-call argument into
the composed method signature whenever a `#[ComposableParameter]` exists, so
you **must** annotate the first constructor parameter for the annotation to be
meaningful:

```php
#[Composable(self::class)]
final readonly class Key implements Validator
{
    public function __construct(
        #[ComposableParameter] private string $key,
        private Validator $validator,
    ) {}
}
// keyEmail('myKey') → Key('myKey', Email())
```

## Builders

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

## Factories

### FluentFactory

Interface implemented by both factories:

```php
interface FluentFactory
{
    public function create(string $name, array $arguments = []): object;
    public function withNamespace(string $namespace): static;
}
```

### NamespaceLookup

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

### ComposingLookup

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

## Resolvers

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

### Ucfirst

Capitalizes the first letter: `'email'` → `'Email'`.
Unresolve does the opposite: `'Email'` → `'email'`.

### Suffix

Strips a prefix and appends a suffix: `Suffix('of', 'Handler')` turns
`'ofArray'` → `'ArrayHandler'`.
Unresolve reverses it: `'ArrayHandler'` → `'ofArray'`.

### ComposableAttributes

Discovers `#[Composable]` attributes at runtime and decomposes prefixed names:
`'notEmail'` → `FluentNode('Email', wrapper: FluentNode('Not'))`.

```php
$resolver = new ComposableAttributes($lookup);
```

Caches prefix discoveries, suffix constraints, and negative lookups for
performance. Unresolve flattens wrapper structures back to flat names.

### ComposableMap

Pre-built resolver using a compiled prefix map instead of runtime discovery.
Useful for code-generated setups where all prefixes are known ahead of time:

```php
$resolver = new ComposableMap(
    composable: ['not' => true, 'nullOr' => true],
    composableWithArgument: ['key' => true],
    forbidden: ['Not' => ['not' => true]],  // suffix => [prefix => true]
);
```

## FluentNode

Readonly data class carrying resolution state:

```php
new FluentNode(
    name: 'Email',
    arguments: ['strict' => true],
    wrapper: new FluentNode('Not'),  // optional
);
```

## Exceptions

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

### Assurance

Declares what type a node class assures. Applied to node classes (repeatable).
FluentAnalysis reads this to determine how each node narrows a type:

```php
// Fixed type
#[Assurance(type: 'int')]
final readonly class IntType implements Validator { /* ... */ }

// Instance of the class named by a class-string argument
// (#[AssuranceParameter] selects which argument; from: TypeString the derivation)
#[Assurance(from: AssuranceFrom::TypeString, exact: true)]
final readonly class Instance implements Validator
{
    public function __construct(
        #[AssuranceParameter] private string $class,
    ) {}
}

// Type from the argument's literal value
#[Assurance(from: AssuranceFrom::Value)]
final readonly class Identical implements Validator
{
    public function __construct(private mixed $compareTo) {}
}

// Type from the iterable value type of the argument
#[Assurance(from: AssuranceFrom::Member)]
final readonly class In implements Validator
{
    public function __construct(private array $haystack) {}
}

// Array of the inner assurance type
#[Assurance(from: AssuranceFrom::Elements)]
final readonly class Each implements Validator
{
    public function __construct(private Validator $itemValidator) {}
}

// Combine assurances from multiple builder arguments
#[Assurance(compose: AssuranceCompose::Union)]
final readonly class AnyOf implements Validator
{
    public function __construct(Validator ...$validators) {}
}

// Combine from a subset of arguments
#[Assurance(compose: AssuranceCompose::Union, composeRange: [1, null])]
final readonly class When implements Validator
{
    public function __construct(Validator $when, Validator $then, Validator $else) {}
}

// Exact: passes if and only if the input is of the declared type
#[Assurance(type: 'int', exact: true)]
final readonly class IntType implements Validator { /* ... */ }

// Modifier on a Wrap prefix: negate the wrapped node's assurance
#[Assurance(modifier: AssuranceModifier::Exclude)]
#[AssuranceSubject(AssuranceSubjectMode::Wrap)]
final readonly class Not implements Validator { /* ... */ }

// Bypass set on a Wrap prefix: 'null' is admitted in union with the
// wrapped node's assurance (nullOrIntType() assures int|null)
#[Assurance(type: 'null', exact: true)]
#[AssuranceSubject(AssuranceSubjectMode::Wrap)]
final readonly class NullOr implements Validator { /* ... */ }
```

Properties:

| Property       | Type                         | Purpose                                                          |
|----------------|------------------------------|------------------------------------------------------------------|
| `type`         | `string\|list<string>\|null` | Fixed type string (e.g. `'int'`); a list means their union       |
| `from`         | `?AssuranceFrom`             | Derive type from a method argument                               |
| `compose`      | `?AssuranceCompose`          | Combine assurances from child validators                         |
| `composeRange` | `?array{int, int\|null}`     | Subset of arguments to compose (`[from, to]`, null = open-ended) |
| `modifier`     | `?AssuranceModifier`         | Modify how the assurance is applied                              |
| `exact`        | `bool`                       | The node passes *iff* the input is of the declared type          |

### AssuranceFrom (enum)

Determines how the assured type is derived from a method argument:

| Case       | Meaning                                                 |
|------------|---------------------------------------------------------|
| `Value`      | The argument's literal type (e.g. `42` → `42`)              |
| `Member`     | The iterable value type (e.g. `['a','b']` → `'a'\|'b'`)     |
| `Elements`   | An array of the inner assurance type                        |
| `TypeString` | An instance of the class named by a class-string argument   |

### AssuranceCompose (enum)

Determines how child assurances are combined:

| Case        | Meaning                                   |
|-------------|-------------------------------------------|
| `Union`     | Union of all child assurance types        |
| `Intersect` | Intersection of all child assurance types |

### AssuranceModifier (enum)

Modifies how an assurance is applied:

| Case      | Meaning                                                               |
|-----------|-----------------------------------------------------------------------|
| `Exclude` | The wrapped node's assurance is negated: passing implies NOT the type |

### AssuranceSubject

Declares how a `#[Composable]` prefix relates to its wrapped node's subject.
A prefix without it yields no assurance for composed names: tools must drop,
not copy, the wrapped node's assurance.

```php
// Same subject, modified: notEmail() negates Email's assurance
#[Assurance(modifier: AssuranceModifier::Exclude)]
#[AssuranceSubject(AssuranceSubjectMode::Wrap)]
final readonly class Not implements Validator { /* ... */ }

// Derived subject: keyEmail('name') assures only the container type
#[Assurance(type: ['array', 'ArrayAccess'])]
#[AssuranceSubject(AssuranceSubjectMode::Container)]
final readonly class Key implements Validator { /* ... */ }
```

### AssuranceSubjectMode (enum)

| Case        | Meaning                                                                          |
|-------------|----------------------------------------------------------------------------------|
| `Wrap`      | Same subject as the wrapped node: its assurance passes through, modified         |
| `Elements`  | The wrapped node validates each element: assurance becomes `iterable<T>`         |
| `Container` | The wrapped node validates a derived subject: only the container type is assured |

A `Wrap` prefix's own `#[Assurance(type:)]` declares its *bypass set*: inputs
it admits itself, in union with whatever the wrapped node assures. It is only
meaningful in composition, never as a claim about direct calls; `exact` on it
means the bypass set is an exact characterization.
