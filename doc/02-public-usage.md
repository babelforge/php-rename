# Public Usage

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md) | [Next: Architecture](03-architecture.md)

The public API should remain small and composable.

## Conflict Policy

Rename methods accept an optional conflict policy:

```php
use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;

$result = $renamer->renameMethod(
    className: App\Service\UserMailer::class,
    methodName: 'send',
    newMethodName: 'deliver',
    conflictPolicy: RenameConflictPolicy::REPORT,
);
```

The default policy is `RenameConflictPolicy::FAIL`. It emits an error diagnostic when `member-graph` scope facts show that the replacement name already exists in the relevant semantic scope, and the plan is not applied.

`RenameConflictPolicy::REPORT` emits a warning diagnostic and keeps the plan applicable.

Some semantic warnings are independent of conflict policy. For example, renaming from or to a PHP magic method name emits a warning because it can change runtime behavior, but the plan remains applicable unless another error diagnostic is present.

## Input Validation And No-Op Renames

Rename requests validate PHP identifiers and FQCN-like names before planning.

Invalid names throw `InvalidArgumentException` before `member-graph` lookup.

No-op renames produce an empty plan with a warning diagnostic and do not mutate virtual files:

```php
$result = $renamer->renameMethod(
    className: App\Service\UserMailer::class,
    methodName: 'send',
    newMethodName: 'send',
);
```

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

## Use A Rename Transaction

Use a transaction when later rename actions depend on earlier in-memory AST mutations:

```php
$transaction = $renamer->beginTransaction();

$transaction->renameClassFqcn('App\\Mailer', 'App\\Infrastructure\\Sender');
$transaction->renameMethod('App\\Infrastructure\\Sender', 'send', 'deliver');

$result = $transaction->commit();
```

After each successful action, the transaction records a `member-graph` overlay update and receives a projected in-memory build. If an action cannot be represented by the overlay, the transaction falls back to a cache-free rebuild from mutated virtual files. `commit()` keeps the result in memory and does not refresh the persistent graph cache.

To commit and write every updated physical source file through the source registry used by `member-graph`:

```php
$result = $transaction->commitAndSave();
```

To commit and write one updated physical source file:

```php
$result = $transaction->commitAndSaveSourceFile('/project/src/App/Mailer.php');
```

If a rename action produces a blocking diagnostic, the transaction enters a failed state and no later action can be executed. Call `rollback()` to restore virtual files touched by earlier successful actions:

```php
$rollbackResult = $transaction->rollback();
```

## Execute Orchestrable Rename Steps

Use the step API when an external orchestrator owns the broader workflow and wants to call `php-rename` as one autonomous service:

```php
use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpRename\Domain\Rename\Step\RenameStepContext;

$renamer = PhpRename::fromBuild($build);
$context = RenameStepContext::fromBuild($build);

$firstStep = $renamer->executeStepClassFqcnRename(
    context: $context,
    className: 'App\\Mailer',
    newClassName: 'App\\Infrastructure\\Sender',
);

$secondStep = $renamer->executeStepMethodRename(
    context: $firstStep->context,
    className: 'App\\Infrastructure\\Sender',
    methodName: 'send',
    newMethodName: 'deliver',
);
```

Each step plans against the context current build, applies the AST mutation when the plan has no blocking diagnostics, records the cumulative `member-graph` overlay when the request is supported by projection, and returns a new context for the next step.

The step API is intentionally service-specific. A future orchestration package such as `php-refactor` should adapt its own transaction context to `RenameStepContext` instead of requiring `php-rename` to depend on orchestrator contracts.

The step API is transaction-neutral:

- it does not create source snapshots;
- it does not restore source snapshots;
- it does not own rollback policy;
- when `applied` is `true`, `touchedFiles` lists the virtual files mutated by the step;
- when `applied` is `false`, the returned `context` is the input context and the caller decides whether to stop or continue;
- blocking diagnostics should normally make an external orchestrator stop the global transaction and restore its own snapshots.

`PhpRenameTransaction` is the local transaction wrapper for standalone `php-rename` usage. It uses the same step execution path, but it adds local snapshots, local rollback, local status transitions, and local commit/save helpers. A global orchestrator should call `executeStep...Rename()` directly instead of nesting `PhpRenameTransaction`.

## Public API Stability

The stable public entry points are:

- `PhpRename::fromDirectory()` and `PhpRename::fromBuild()`;
- direct planning methods named `plan...Rename()`;
- direct application methods named `rename...()`;
- transaction methods exposed by `PhpRenameTransaction`;
- orchestrable step methods named `executeStep...Rename()`;
- `RenameStepContext` and `RenameStepResult` for orchestration.

`php-refactor` should consume the orchestrable step methods and keep its own adapters outside this package.

Infrastructure classes under `Infrastructure/` are implementation details. They can be replaced or reorganized as long as the public facade, transaction API, step API, diagnostics, plans, and results keep their documented behavior.

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

## Plan And Apply An Enum-Case Rename

Enum-case rename has a dedicated API even though it reuses the same `member-graph` source-node lookup path as class constants:

```php
$plan = $renamer->planEnumCaseRename(
    enumName: App\Domain\Status::class,
    caseName: 'ACTIVE',
    newCaseName: 'ENABLED',
);

$result = $renamer->renameEnumCase(
    enumName: App\Domain\Status::class,
    caseName: 'ACTIVE',
    newCaseName: 'ENABLED',
);
```

The enum-case planner uses `member-graph` source-node matches only. The applier supports enum-case declarations, enum-case fetches, and supported enum-case docblock references.

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

Function short rename does not move functions between namespaces. Existing `use function` imports for the renamed function are rewritten to the renamed FQCN.

## Plan And Apply Parameter Renames

Method and function parameter renames support an optional zero-based declaration index:

```php
$result = $renamer->renameMethodParameter(
    className: App\Service\UserMailer::class,
    methodName: 'send',
    parameterName: 'message',
    newParameterName: 'emailMessage',
    parameterIndex: 0,
);

$result = $renamer->renameFunctionParameter(
    functionName: 'App\\send_mail',
    parameterName: 'message',
    newParameterName: 'emailMessage',
    parameterIndex: 0,
);
```

When `parameterIndex` is provided, `member-graph` must match both the parameter name and its declaration index. The current slice mutates parameter declarations, named arguments, local parameter usages returned by `member-graph`, and supported `@param` docblock tags.

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

## Plan And Apply A Constant Rename

Namespace-level constant rename uses the fully-qualified current constant name and a short replacement name:

```php
$plan = $renamer->planConstantRename(
    constantName: 'App\\Config\\ENABLED',
    newConstantName: 'ACTIVE',
);

$result = $renamer->renameConstant(
    constantName: 'App\\Config\\ENABLED',
    newConstantName: 'ACTIVE',
);
```

The planner uses `MemberGraphSourceNodeLocator::constant(...)`. The applier mutates constant declarations, constant fetches, and existing `use const` imports in touched files.

## Plan And Apply A Constant FQCN Rename

Constant FQCN rename changes the full logical namespace-level constant name:

```php
$plan = $renamer->planConstantFqcnRename(
    constantName: 'App\\Config\\ENABLED',
    newConstantName: 'Tools\\ACTIVE',
);

$result = $renamer->renameConstantFqcn(
    constantName: 'App\\Config\\ENABLED',
    newConstantName: 'Tools\\ACTIVE',
);
```

The declaration namespace and short constant name are mutated in memory. Constant fetches returned by `member-graph` are rewritten to the replacement short name, and the containing namespace gets a `use const` import when needed. If an import alias collision is reported with `RenameConflictPolicy::REPORT`, application falls back to a fully-qualified constant fetch.

Navigation: [Documentation](README.md) | [Previous: Overview](01-overview.md) | [Next: Architecture](03-architecture.md)
