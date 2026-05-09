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

After successful node mutation, each touched `VirtualPhpSourceFile` is marked as updated through `VirtualPhpSourceFile::update()`.

Unsupported operation kinds or node types produce diagnostics instead of triggering fallback source inspection.

## Docblocks

Docblock mutation is not implemented yet.

When added, it must remain attached to matched nodes or their direct structural owners. It must not scan unrelated files or comments.

## Future Physical Writing

Physical writing should be a separate layer.

That later layer can decide how to:

- pretty-print virtual files;
- preserve formatting;
- update physical files;
- expose diffs or write changes to disk.

Navigation: [Documentation](README.md) | [Previous: Rename Planning](04-rename-planning.md) | [Next: Testing And Maintenance](06-testing-and-maintenance.md)
