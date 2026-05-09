# Rename Planning

Navigation: [Documentation](README.md) | [Previous: Architecture](03-architecture.md) | [Next: AST Application](05-ast-application.md)

Rename planning answers one question:

```text
Which AST nodes must be changed for this rename to be semantically correct?
```

Planning must not mutate virtual files.

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

`MemberGraphMethodRenamePlanner` currently returns:

- an empty `RenameOperationCollection`;
- an informational diagnostic saying semantic method rename planning is not implemented yet.

The next implementation step is to inspect and use the relevant `member-graph` query, impact, and source-node APIs.

Navigation: [Documentation](README.md) | [Previous: Architecture](03-architecture.md) | [Next: AST Application](05-ast-application.md)
