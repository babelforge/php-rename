# PhpRename Documentation

Navigation: [Next: Overview](01-overview.md)

This documentation describes the `PhpRename` component, how to use it, and the package boundaries that govern its behavior.

`PhpRename` is a PHP refactoring library focused on safe symbol renaming. It consumes `php-noobs/member-graph` for semantic dependency facts and owns rename planning plus AST mutation.

The package contains semantic planning and AST mutation for the supported rename matrix. Physical file writing is delegated to `php-source-registry`, while namespace-wide refactors and cross-service transactions belong to orchestration packages.

The public API is stable for the documented rename matrix. Users can rely on the facade, transaction API, step API, plan/result DTOs, and diagnostics contracts described in these pages.

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

`PhpRename` does not duplicate member graph logic. It uses upstream semantic facts to decide what can be renamed safely.

## Current Layout

The general rule is:

- `Domain/` contains rename intents, plans, operations, results, and diagnostics.
- `Application/` contains the public facade and contracts for use-case services.
- `Infrastructure/MemberGraph/` adapts `member-graph` facts into rename plans.
- `Infrastructure/PhpParser/` applies rename plans to PHPParser AST nodes stored in virtual files.

Navigation: [Next: Overview](01-overview.md)
