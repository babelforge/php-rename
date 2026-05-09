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
- `planMethodRename()` and `renameMethod()` public methods;
- domain DTOs for plans, operations, results, and diagnostics;
- contracts for planning and applying rename plans;
- a placeholder `member-graph` planner;
- a placeholder AST applier.

The current implementation does not yet perform semantic method rename planning or AST mutation.

## Direction

The first real feature should be method rename planning:

```php
$plan = $renamer->planMethodRename(
    className: App\Service\UserMailer::class,
    methodName: 'send',
    newMethodName: 'deliver',
);
```

The plan should include declarations and consumers resolved by `member-graph`, including parents, children, traits, and related usages when they are semantically known.

Navigation: [Documentation](README.md) | [Previous: Documentation](README.md) | [Next: Public Usage](02-public-usage.md)
