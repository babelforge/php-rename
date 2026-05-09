# AST Application

Navigation: [Documentation](README.md) | [Previous: Rename Planning](04-rename-planning.md) | [Next: Testing And Maintenance](06-testing-and-maintenance.md)

AST application mutates virtual PHP files in memory from a `RenamePlan`.

It should not write physical files in the first milestones.

The applier must not discover rename targets. It only mutates nodes already present in the rename plan.

## Input

The applier receives:

- a `RenamePlan`;
- the `MemberDependencyGraphBuild` containing virtual files.

Each `RenameOperation` references:

- the virtual file;
- the PHPParser node to mutate;
- the symbol kind;
- the operation role;
- the old name;
- the new name.

## Output

The applier returns a `RenameResult` containing:

- the applied plan;
- the virtual files after mutation;
- application diagnostics.

## Current Implementation

`AstRenamePlanApplier` currently supports method renaming for:

- `PhpParser\Node\Stmt\ClassMethod`;
- `PhpParser\Node\Expr\MethodCall`;
- `PhpParser\Node\Expr\NullsafeMethodCall`;
- `PhpParser\Node\Expr\StaticCall`.

It also supports property renaming for:

- `PhpParser\Node\Stmt\PropertyProperty`;
- `PhpParser\Node\Expr\PropertyFetch`;
- `PhpParser\Node\Expr\StaticPropertyFetch`;
- promoted-property `PhpParser\Node\Param` declarations.

After successful node mutation, each touched `VirtualPhpSourceFile` is marked as updated through `VirtualPhpSourceFile::update()`.

Unsupported operation kinds or node types produce diagnostics instead of triggering fallback source inspection.

## Docblocks

Docblock mutation is implemented as metadata application after a node mutation succeeds.

Current supported method docblock references:

```php
self::oldName()
static::oldName()
parent::oldName()
```

These references are renamed only on matched method declaration docblocks.

Current supported property docblock references:

```php
self::$oldName
static::$oldName
parent::$oldName
```

These references are renamed only on matched property declaration docblocks through the parent `Property` node.

Free-text descriptions are not rewritten. The implementation does not scan unrelated files or comments.

## Future Physical Writing

Physical writing should be a separate layer.

That later layer can decide how to:

- pretty-print virtual files;
- preserve formatting;
- update physical files;
- expose diffs or write changes to disk.

Navigation: [Documentation](README.md) | [Previous: Rename Planning](04-rename-planning.md) | [Next: Testing And Maintenance](06-testing-and-maintenance.md)
