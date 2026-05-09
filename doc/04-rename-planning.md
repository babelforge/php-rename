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
