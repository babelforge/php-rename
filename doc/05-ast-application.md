# AST Application

Navigation: [Documentation](README.md) | [Previous: Rename Planning](04-rename-planning.md) | [Next: Testing And Maintenance](06-testing-and-maintenance.md)

AST application mutates virtual PHP files in memory from a `RenamePlan`.

It should not write physical files in the first milestones.

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

`AstRenamePlanApplier` currently returns the build virtual files unchanged.

The first real implementation should support method declaration and method call node renaming, then expand only after the method workflow is tested.

## Future Physical Writing

Physical writing should be a separate layer.

That later layer can decide how to:

- pretty-print virtual files;
- preserve formatting;
- update physical files;
- expose diffs or write changes to disk.

Navigation: [Documentation](README.md) | [Previous: Rename Planning](04-rename-planning.md) | [Next: Testing And Maintenance](06-testing-and-maintenance.md)
