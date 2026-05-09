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
- method rename planning and AST application against a real `member-graph` build.
- property rename planning and AST application against a real `member-graph` build.
- class-constant rename planning and AST application against a real `member-graph` build.
- function rename planning and AST application against a real `member-graph` build.

## Testing Direction

The method rename workflow should be tested with small readable fixtures.

Priority cases:

- direct method declaration;
- direct method call;
- direct property declaration;
- direct property fetch;
- direct class-constant declaration;
- direct class-constant fetch;
- direct function declaration;
- direct function call;
- parent and child method declarations;
- trait method declaration;
- interface method declaration;
- consumers resolved through typed variables;
- unresolved dynamic calls reported as diagnostics.

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
