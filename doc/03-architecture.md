# Architecture

Navigation: [Documentation](README.md) | [Previous: Public Usage](02-public-usage.md) | [Next: Rename Planning](04-rename-planning.md)

The architecture is intentionally small at this stage.

## Domain

`Domain/Rename` contains the core rename model:

- `MethodRenameRequest`: describes the requested method rename.
- `RenamePlan`: contains planned operations and diagnostics.
- `RenameOperation`: targets one AST node in one virtual file.
- `RenameResult`: contains the result of applying a plan.
- `RenameDiagnostic`: reports planning or application information.
- `RenameSymbolKind`: identifies the kind of symbol being renamed.
- `RenameOperationRole`: identifies why a node is part of the plan.

Domain objects should stay independent from orchestration logic.

## Application

`Application/PhpRename` is the public facade.

It exposes:

- `fromDirectory()`;
- `fromBuild()`;
- `planMethodRename()`;
- `renameMethod()`.

`Application/Contract` contains the service contracts used by the facade:

- `MethodRenamePlannerInterface`;
- `RenamePlanApplierInterface`.

## Infrastructure

`Infrastructure/MemberGraph` translates semantic graph facts into rename operations.

`Infrastructure/PhpParser` applies rename operations to PHPParser AST nodes stored in virtual files.

Infrastructure code can depend on external packages. Domain objects should remain simple and explicit.

## Rename Application

`AstRenamePlanApplier` orchestrates specialized appliers:

- node appliers mutate the matched AST node;
- metadata appliers mutate supported metadata attached to a successfully mutated node.

Current applier contracts:

- `RenameNodeApplierInterface`;
- `RenameMetadataApplierInterface`.

Current implementations:

- `MethodRenameNodeApplier`;
- `MethodDocblockRenameApplier`.
- `PropertyRenameNodeApplier`;
- `PropertyDocblockRenameApplier`.
- `ClassConstantRenameNodeApplier`;
- `ClassConstantDocblockRenameApplier`.

## Design Rule

Do not add a broad refactoring abstraction before the method rename path is proven.

The package should grow from concrete safe rename workflows, then generalize only when duplication becomes real.

Navigation: [Documentation](README.md) | [Previous: Public Usage](02-public-usage.md) | [Next: Rename Planning](04-rename-planning.md)
