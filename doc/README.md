# PhpRename Documentation

Navigation: [Next: Overview](01-overview.md)

This documentation describes the `PhpRename` component, how to use it, how it should evolve internally, and which rules should guide future changes.

`PhpRename` is a PHP refactoring library focused on safe symbol renaming. It consumes `php-noobs/member-graph` for semantic dependency facts and owns rename planning plus AST mutation.

The package currently contains semantic planning and AST mutation for the supported rename matrix. Physical file writing, namespace-wide refactors, and cache transactions remain outside the current implementation.

## Pages

1. [Overview](01-overview.md)
2. [Public Usage](02-public-usage.md)
3. [Architecture](03-architecture.md)
4. [Rename Planning](04-rename-planning.md)
5. [AST Application](05-ast-application.md)
6. [Testing And Maintenance](06-testing-and-maintenance.md)
7. [Supported Rename Matrix](07-supported-rename-matrix.md)

## External Dependencies

`PhpRename` consumes `php-noobs/member-graph` to build and query member-level dependency facts.

`member-graph` depends on `php-noobs/php-source-registry`, which provides virtual PHP source files and PHPParser AST access.

`PhpRename` must not duplicate member graph logic. It should use upstream semantic facts to decide what can be renamed safely.

## Current Layout

The general rule is:

- `Domain/` contains rename intents, plans, operations, results, and diagnostics.
- `Application/` contains the public facade and contracts for use-case services.
- `Infrastructure/MemberGraph/` adapts `member-graph` facts into rename plans.
- `Infrastructure/PhpParser/` applies rename plans to PHPParser AST nodes stored in virtual files.

Navigation: [Next: Overview](01-overview.md)
