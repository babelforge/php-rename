# Public Usage

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md) | [Next: Architecture](03-architecture.md)

The public API should remain small and composable.

## Create From Directories

Use this mode when `PhpRename` should build its own `member-graph` input:

```php
use PhpNoobs\PhpRename\Application\PhpRename;

$renamer = PhpRename::fromDirectory(
    directories: [$projectPath . '/src'],
    cacheFilePath: $projectPath . '/var/member-graph.cache',
);
```

## Create From Existing Build

Use this mode when another tool already built a `member-graph` result:

```php
use PhpNoobs\PhpRename\Application\PhpRename;

$renamer = PhpRename::fromBuild($build);
```

This is the preferred integration point for future orchestration packages such as `php-refactor`.

## Plan A Method Rename

Planning should produce operations and diagnostics without mutating virtual files:

```php
$plan = $renamer->planMethodRename(
    className: App\Service\UserMailer::class,
    methodName: 'send',
    newMethodName: 'deliver',
);
```

The current implementation converts `member-graph` source-node matches into rename operations.

## Apply A Method Rename

The convenience method should plan and apply in one call:

```php
$result = $renamer->renameMethod(
    className: App\Service\UserMailer::class,
    methodName: 'send',
    newMethodName: 'deliver',
);
```

The current implementation mutates matched method declaration and usage nodes in virtual files.

## Plan And Apply A Class Rename

Class rename uses the fully-qualified current class-like owner name and a short replacement name:

```php
$plan = $renamer->planClassRename(
    className: App\Service\UserMailer::class,
    newClassName: 'TransactionalMailer',
);

$result = $renamer->renameClass(
    className: App\Service\UserMailer::class,
    newClassName: 'TransactionalMailer',
);
```

The class planner uses `member-graph` owner source-node matches only. The first class rename slice does not move classes between namespaces.

## Plan And Apply A Class FQCN Rename

Class FQCN rename changes the full logical class-like owner name:

```php
$plan = $renamer->planClassFqcnRename(
    className: 'App\\Mailer',
    newClassName: 'App\\Infrastructure\\Sender',
);

$result = $renamer->renameClassFqcn(
    className: 'App\\Mailer',
    newClassName: 'App\\Infrastructure\\Sender',
);
```

The declaration namespace and short declaration name are mutated in memory. Usage names returned by `member-graph` are rewritten to the replacement short name, and the containing namespace gets a normal `use` import when needed. Physical file moves remain out of scope.

## Plan And Apply A Property Rename

Property rename follows the same plan/apply split:

```php
$plan = $renamer->planPropertyRename(
    className: App\Service\UserMailer::class,
    propertyName: 'transport',
    newPropertyName: 'mailerTransport',
);

$result = $renamer->renameProperty(
    className: App\Service\UserMailer::class,
    propertyName: 'transport',
    newPropertyName: 'mailerTransport',
);
```

The property planner uses `member-graph` source-node matches only. The applier supports property declarations, instance property fetches, static property fetches, and promoted-property declaration nodes.

## Plan And Apply A Class-Constant Rename

Class-constant rename follows the same plan/apply split:

```php
$plan = $renamer->planClassConstantRename(
    className: App\Service\UserMailer::class,
    constantName: 'DEFAULT_TRANSPORT',
    newConstantName: 'FALLBACK_TRANSPORT',
);

$result = $renamer->renameClassConstant(
    className: App\Service\UserMailer::class,
    constantName: 'DEFAULT_TRANSPORT',
    newConstantName: 'FALLBACK_TRANSPORT',
);
```

The class-constant planner uses `member-graph` source-node matches only. The applier supports class-constant declarations, enum-case declarations, and class-constant fetches.

## Plan And Apply A Function Rename

Function rename uses the fully-qualified current function name and a short replacement name:

```php
$plan = $renamer->planFunctionRename(
    functionName: 'App\\send_mail',
    newFunctionName: 'deliver_mail',
);

$result = $renamer->renameFunction(
    functionName: 'App\\send_mail',
    newFunctionName: 'deliver_mail',
);
```

The first function rename slice does not move functions between namespaces and does not rewrite `use function` imports.

## Plan And Apply A Function FQCN Rename

Function FQCN rename changes the full logical function name:

```php
$plan = $renamer->planFunctionFqcnRename(
    functionName: 'App\\send_mail',
    newFunctionName: 'Tools\\deliver_mail',
);

$result = $renamer->renameFunctionFqcn(
    functionName: 'App\\send_mail',
    newFunctionName: 'Tools\\deliver_mail',
);
```

The declaration namespace and short function name are mutated in memory. Calls returned by `member-graph` are rewritten to the replacement short name, and the containing namespace gets a `use function` import when needed. Physical file moves remain out of scope.

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md) | [Next: Architecture](03-architecture.md)
