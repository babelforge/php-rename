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
- `RenameConflictPolicy`: defines whether detected conflicts become warnings or blocking errors.
- `RenameSymbolKind`: identifies the kind of symbol being renamed.
- `RenameOperationRole`: identifies why a node is part of the plan.
- `RenameTransactionStatus`: identifies transaction lifecycle state.
- `RenameTransactionResult`: aggregates transaction action results, final build, final virtual files, and diagnostics.

Domain objects should stay independent from orchestration logic.

## Application

`Application/PhpRename` is the public facade.

It exposes:

- `fromDirectory()`;
- `fromBuild()`;
- `beginTransaction()`;
- `planClassRename()`;
- `renameClass()`;
- `planClassFqcnRename()`;
- `renameClassFqcn()`;
- `planMethodRename()`;
- `renameMethod()`;
- `planPropertyRename()`;
- `renameProperty()`;
- `planClassConstantRename()`;
- `renameClassConstant()`;
- `planEnumCaseRename()`;
- `renameEnumCase()`;
- `planFunctionRename()`;
- `renameFunction()`;
- `planFunctionFqcnRename()`;
- `renameFunctionFqcn()`;
- `planConstantRename()`;
- `renameConstant()`;
- `planConstantFqcnRename()`;
- `renameConstantFqcn()`;
- `planMethodParameterRename()`;
- `renameMethodParameter()`;
- `planFunctionParameterRename()`;
- `renameFunctionParameter()`.

`Application/PhpRenameTransaction` mirrors the direct rename methods and maintains a cumulative `member-graph` overlay during the transaction. After each successful supported action, it asks `member-graph` for a projected build. Unsupported actions can still fall back to `MemberDependencyGraphFactory::fromVirtualFiles(...)`.

`commit()` finalizes the in-memory transaction. `commitAndSave()` and `commitAndSaveSourceFile()` finalize the transaction and delegate physical writing to the `sourceRegistry()` exposed by the final `member-graph` build.

`Application/Contract` contains the service contracts used by the facade:

- `MethodRenamePlannerInterface`;
- `ClassRenamePlannerInterface`;
- `ClassFqcnRenamePlannerInterface`;
- `PropertyRenamePlannerInterface`;
- `ClassConstantRenamePlannerInterface`;
- `EnumCaseRenamePlannerInterface`;
- `FunctionRenamePlannerInterface`;
- `FunctionFqcnRenamePlannerInterface`;
- `ConstantRenamePlannerInterface`;
- `ConstantFqcnRenamePlannerInterface`;
- `ParameterRenamePlannerInterface`;
- `RenamePlanApplierInterface`.

## Infrastructure

`Infrastructure/MemberGraph` translates semantic graph facts into rename operations. It also converts neutral `member-graph` scope facts into `php-rename` diagnostics according to the request conflict policy.

`Infrastructure/PhpParser` applies rename operations to PHPParser AST nodes stored in virtual files.

`Infrastructure/PhpParser/Transaction` contains virtual-file snapshot support used by transaction rollback.

Infrastructure code can depend on external packages. Domain objects should remain simple and explicit.

## Rename Application

`AstRenamePlanApplier` orchestrates specialized appliers:

- it does not apply plans that contain error diagnostics;
- node appliers mutate the matched AST node;
- metadata appliers mutate supported metadata attached to a successfully mutated node.

Current applier contracts:

- `RenameNodeApplierInterface`;
- `RenameMetadataApplierInterface`.

Current implementations:

- `ClassRenameNodeApplier`;
- `ClassDocblockRenameApplier`.
- `MethodRenameNodeApplier`;
- `MethodDocblockRenameApplier`.
- `PropertyRenameNodeApplier`;
- `PropertyDocblockRenameApplier`.
- `ClassConstantRenameNodeApplier`;
- `ClassConstantDocblockRenameApplier`.
- `FunctionRenameNodeApplier`;
- `FunctionDocblockRenameApplier`.
- `ConstantRenameNodeApplier`;
- `ParameterRenameNodeApplier`;
- `ParameterDocblockRenameApplier`.

## Design Rule

Do not add a broad refactoring abstraction before the method rename path is proven.

The package should grow from concrete safe rename workflows, then generalize only when duplication becomes real.

Navigation: [Documentation](README.md) | [Previous: Public Usage](02-public-usage.md) | [Next: Rename Planning](04-rename-planning.md)
