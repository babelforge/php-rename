# Rename Planning

Navigation: [Documentation](README.md) | [Previous: Architecture](03-architecture.md) | [Next: AST Application](05-ast-application.md)

Rename planning answers one question:

```text
Which AST nodes must be changed for this rename to be semantically correct?
```

Planning must not mutate virtual files.

## Source Of Truth

`member-graph` is the only source of truth for deciding which source nodes belong to a rename.

`PhpRename` must not:

- perform textual search;
- traverse the AST to discover additional candidates;
- rebuild inheritance, trait, interface, or consumer scopes;
- apply fallback replacements on nodes that only look similar.

For method renaming, planning starts from:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$matches = MemberGraphSourceNodeLocator::fromBuild($build)
    ->method('App\\Mailer', 'send');
```

The returned `VirtualPhpSourceFileNodeMatchCollection` is converted to rename operations.

For class renaming, planning starts from:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$matches = MemberGraphSourceNodeLocator::fromBuild($build)
    ->owner('App\\Mailer');
```

The first class rename slice expects a fully-qualified current class-like owner name and a short replacement class name. It does not move classes between namespaces.

For class FQCN renaming, planning starts from the same source of truth:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$matches = MemberGraphSourceNodeLocator::fromBuild($build)
    ->owner('App\\Mailer');
```

The difference is the replacement contract: `renameClassFqcn()` receives a fully-qualified replacement owner name. The planner still does not discover additional candidates by itself.

For property renaming, planning starts from:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$matches = MemberGraphSourceNodeLocator::fromBuild($build)
    ->property('App\\Mailer', 'transport');
```

The same source-of-truth rule applies.

For class-constant renaming, planning starts from:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$matches = MemberGraphSourceNodeLocator::fromBuild($build)
    ->classConstant('App\\Mailer', 'DEFAULT_TRANSPORT');
```

The same source-of-truth rule applies.

For function renaming, planning starts from:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$matches = MemberGraphSourceNodeLocator::fromBuild($build)
    ->function('App\\send_mail');
```

The first function rename slice expects a fully-qualified current function name and a short replacement function name. It does not move functions between namespaces.

For function FQCN renaming, planning starts from the same source of truth:

```php
use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;

$matches = MemberGraphSourceNodeLocator::fromBuild($build)
    ->function('App\\send_mail');
```

The difference is the replacement contract: `renameFunctionFqcn()` receives a fully-qualified replacement function name. The planner still does not discover additional candidates by itself.

## Method Rename Scope

For method renaming, the default scope is semantic.

Given:

```php
$renamer->planMethodRename(ClassName::class, 'oldName', 'newName');
```

The planner should include:

- the target declaration;
- parent declarations linked to the same method contract;
- child declarations overriding or implementing the method;
- trait declarations and trait adaptations when relevant;
- interface declarations when relevant;
- resolved consumers/usages;
- `self::`, `static::`, `parent::`, object calls, and static calls when `member-graph` can resolve them safely.

## Diagnostics

The planner must report diagnostics instead of silently guessing.

Examples:

- target method not found;
- replacement name collides with an existing method;
- usage cannot be resolved safely;
- dynamic call is ambiguous;
- source node cannot be located.

## Current Implementation

`MemberGraphMethodRenamePlanner` currently:

- calls `MemberGraphSourceNodeLocator::fromBuild($build)->method(...)`;
- converts member declaration matches to declaration rename operations;
- converts member usage matches to usage rename operations;
- emits a warning diagnostic when no source-node match is found.

`MemberGraphClassRenamePlanner` follows the same pattern with `MemberGraphSourceNodeLocator::owner(...)`.

`MemberGraphClassFqcnRenamePlanner` also follows the same pattern with `MemberGraphSourceNodeLocator::owner(...)`, but stores fully-qualified old and new owner names in the rename operations.

`MemberGraphPropertyRenamePlanner` follows the same pattern with `MemberGraphSourceNodeLocator::property(...)`.

`MemberGraphClassConstantRenamePlanner` follows the same pattern with `MemberGraphSourceNodeLocator::classConstant(...)`.

`MemberGraphFunctionRenamePlanner` follows the same pattern with `MemberGraphSourceNodeLocator::function(...)`.

`MemberGraphFunctionFqcnRenamePlanner` also follows the same pattern with `MemberGraphSourceNodeLocator::function(...)`, but stores fully-qualified old and new function names in the rename operations.

The planner intentionally does not search source code by itself.

## Docblock Boundary

Docblocks are source metadata and will need dedicated handling.

The same source-of-truth rule applies:

- a docblock can be mutated only when it belongs to a matched node;
- a docblock can be mutated when it belongs to the direct structural owner of a matched node;
- no global docblock search is allowed.

Examples:

- `ClassMethod` match: the method docblock is eligible.
- `Param` match: the parent `ClassMethod` or `Function_` docblock is eligible.
- `PropertyProperty` match: the parent `Property` docblock is eligible.
- `Const_` match: the parent `ClassConst` docblock is eligible.

Parent links are expected because `php-source-registry` uses `UserLandParser`, and `UserLandParser` runs a parent-connecting visitor.

Navigation: [Documentation](README.md) | [Previous: Architecture](03-architecture.md) | [Next: AST Application](05-ast-application.md)
