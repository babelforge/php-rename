# Overview

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)

`PhpRename` is designed as a small specialized library, not as a general refactoring framework.

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

Physical file writing and source reassembly are not part of the first milestone.

## Current Status

The current implementation provides:

- `PhpRename` public facade;
- `fromDirectory()` and `fromBuild()` construction paths;
- plan/apply APIs for class-like owners, methods, properties, class constants, enum cases, functions, namespace-level constants, and parameters;
- domain DTOs for plans, operations, results, and diagnostics;
- contracts for planning and applying rename plans;
- `member-graph` planners that convert source-node matches into rename operations;
- PHPParser appliers that mutate matched AST nodes in virtual files;
- conflict policy with blocking and report-only modes.

The current implementation does not write physical files, move file paths, rename whole namespaces, or rebuild `member-graph` caches after mutation.

## Boundary Decisions

Namespace-wide rename is not currently a first-class `php-rename` operation. It is broader than a single symbol rename because it affects many declarations, imports, FQCN references, and often physical paths. That orchestration belongs to a future higher-level package such as `php-refactor`.

FQCN symbol renames can update the namespace node of a matched declaration, but they intentionally do not move files on disk. A later writer or refactor layer can consume the updated virtual files and decide whether to move paths.

## Direction

The next larger concern is transaction/cache behavior: batching multiple rename operations, refreshing semantic facts after mutation, and rolling back virtual-file mutations when a batch fails.

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)
