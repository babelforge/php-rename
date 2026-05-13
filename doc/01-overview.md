# Overview

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)

`PhpRename` is a small specialized library, not a general refactoring framework.

Its responsibility is to rename PHP symbols safely by combining:

- semantic facts from `member-graph`;
- explicit rename plans;
- AST-level mutations on virtual PHP files.

## Package Boundary

`member-graph` owns read-side analysis:

- declarations;
- usages;
- inheritance relationships;
- trait and interface relationships;
- source-node lookup;
- impacted files and owners.

`PhpRename` owns write-side preparation:

- rename requests;
- rename planning;
- diagnostics;
- AST rename operations;
- applying operations to loaded virtual files.

Physical file writing is delegated to `php-source-registry` through the source registry exposed by `member-graph`.

## Supported Features

`php-rename` provides:

- `PhpRename` public facade;
- `fromDirectory()` and `fromBuild()` construction paths;
- plan/apply APIs for class-like owners, methods, properties, class constants, enum cases, functions, namespace-level constants, parameters, nested callable parameters, and nested callable local variables;
- domain DTOs for plans, operations, results, and diagnostics;
- contracts for planning and applying rename plans;
- `member-graph` planners that convert source-node matches into rename operations;
- PHPParser appliers that mutate matched AST nodes in virtual files;
- conflict policy with blocking and report-only modes;
- transaction helpers for in-memory commits, rollback, and optional source-registry writing.

`php-rename` does not move file paths, rename whole namespaces, or rebuild `member-graph` caches after mutation.

## Boundary Decisions

Namespace-wide rename is not a first-class `php-rename` operation. It is broader than a single symbol rename because it affects many declarations, imports, FQCN references, and often physical paths. That orchestration belongs to a higher-level package such as `php-refactor`.

FQCN symbol renames can update the namespace node of a matched declaration, but they intentionally do not move files on disk. A later writer or refactor layer can consume the updated virtual files and decide whether to move paths.

## Stable Scope

`php-rename` focuses on safe symbol rename operations. Broader orchestration such as namespace-wide refactors, file moves, and multi-step clone/extract workflows belongs in a higher-level package such as `php-refactor`.

The documented facade, transaction, step, plan, result, and diagnostic APIs form the stable integration surface for the supported rename matrix.

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)
