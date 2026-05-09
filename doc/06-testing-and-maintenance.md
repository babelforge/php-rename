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

## Testing Direction

The method rename workflow should be tested with small readable fixtures.

Priority cases:

- direct method declaration;
- direct method call;
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
- Keep physical file writing out of the first rename milestones.
- Run PHPStan on both `src` and `tests` before considering a step complete.

Navigation: [Documentation](README.md) | [Previous: AST Application](05-ast-application.md)
