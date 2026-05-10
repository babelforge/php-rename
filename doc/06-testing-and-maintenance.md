# Testing And Maintenance

Navigation: [Documentation](README.md) | [Previous: AST Application](05-ast-application.md)

Tests should protect behavior before broadening the API.

## Current Validation Commands

```bash
composer validate --strict
composer cs
vendor/bin/phpstan analyse src --no-progress --error-format=table
vendor/bin/phpstan analyse tests --no-progress --error-format=table
vendor/bin/phpunit
```

## Current Tests

The current test suite covers:

- `MethodRenameRequest` validation;
- `RenameDiagnosticCollection` counting and iteration.
- class rename planning and AST application against a real `member-graph` build.
- class FQCN rename planning and AST application against a real `member-graph` build.
- method rename planning and AST application against a real `member-graph` build.
- property rename planning and AST application against a real `member-graph` build.
- class-constant rename planning and AST application against a real `member-graph` build.
- function rename planning and AST application against a real `member-graph` build.
- function FQCN rename planning and AST application against a real `member-graph` build.
- parameter rename planning and AST application against a real `member-graph` build.
- conflict policy for blocking errors and report-only warnings across every supported rename family.
- no-op rename planning across every supported rename family.
- request validation for invalid identifiers and FQCN-like names.

## Testing Direction

The method rename workflow should be tested with small readable fixtures.

Priority cases:

- direct class-like owner declaration;
- direct class-like owner usage;
- class-like owner namespace move;
- direct method declaration;
- direct method call;
- direct property declaration;
- direct property fetch;
- direct class-constant declaration;
- direct class-constant fetch;
- direct function declaration;
- direct function call;
- function namespace move;
- function import update;
- parameter declaration rename;
- named argument rename;
- local parameter usage rename;
- `@param` docblock rename;
- parent and child method declarations;
- trait method declaration;
- interface method declaration;
- consumers resolved through typed variables;
- unresolved dynamic calls reported as diagnostics.
- conflict policy blocks application when configured as `FAIL`;
- conflict policy reports warnings while allowing application when configured as `REPORT`.
- invalid rename inputs throw before planning;
- no-op renames produce warning diagnostics and empty plans.

## Maintenance Rules

- Keep comments and PHPDoc in English.
- Keep public APIs explicit.
- Prefer DTOs and collections over associative arrays.
- Keep `member-graph` analysis concerns out of this package.
- Treat `member-graph` source-node matches as the only rename source of truth.
- Do not add textual search or fallback AST traversal in `php-rename`.
- Keep physical file writing out of the first rename milestones.
- Run PHPStan on both `src` and `tests` before considering a step complete.

Navigation: [Documentation](README.md) | [Previous: AST Application](05-ast-application.md)
