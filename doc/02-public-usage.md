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

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md) | [Next: Architecture](03-architecture.md)
