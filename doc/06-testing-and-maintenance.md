# Testing And Maintenance

Navigation: [Documentation](README.md) | [Previous: AST Application](05-ast-application.md) | [Next: Supported Rename Matrix](07-supported-rename-matrix.md)

Tests protect behavior before broadening the API.

## Validation Commands

```bash
composer validate --strict
composer cs
vendor/bin/phpstan analyse src --no-progress --error-format=table
vendor/bin/phpstan analyse tests --no-progress --error-format=table
vendor/bin/phpunit
```

## Test Coverage

The test suite covers:

- `MethodRenameRequest` validation;
- `RenameDiagnosticCollection` counting and iteration.
- class rename planning and AST application against a real `member-graph` build.
- class FQCN rename planning and AST application against a real `member-graph` build.
- method rename planning and AST application against a real `member-graph` build.
- property rename planning and AST application against a real `member-graph` build.
- class-constant rename planning and AST application against a real `member-graph` build.
- function rename planning and AST application against a real `member-graph` build.
- function FQCN rename planning and AST application against a real `member-graph` build.
- namespace-level constant rename planning and AST application against a real `member-graph` build.
- namespace-level constant FQCN rename planning and AST application against a real `member-graph` build.
- parameter rename planning and AST application against a real `member-graph` build.
- nested callable parameter rename planning and AST application for closures, arrow functions, direct usage, explicit and implicit captures, shadowed scopes, step execution, and transactions.
- conflict policy for blocking errors and report-only warnings across every supported rename family.
- no-op rename planning across every supported rename family.
- request validation for invalid identifiers and FQCN-like names.
- case-sensitivity rules for rename conflict policy.
- class-like and function import alias conflicts for FQCN renames, including normal imports, grouped imports, and explicit aliases.
- short function rename import rewrites for existing `use function` imports.
- promoted property renames, including constructor-local parameter usages and conflicts with normal or promoted properties.
- trait adaptation method renames, including alias sources, precedence references, and projected consumer calls.
- explicit enum-case rename API coverage.
- magic method semantic warning diagnostics.
- expanded structured docblock references, including namespace-level constant `@see` references.
- transaction rebuild behavior for dependent rename actions.
- transaction rollback after a later blocking diagnostic.

## Testing Direction

Rename workflows are tested with small readable fixtures.

Priority cases:

- direct class-like owner declaration;
- direct class-like owner usage;
- class-like owner namespace move;
- direct method declaration;
- direct method call;
- magic method semantic warnings;
- direct property declaration;
- direct property fetch;
- promoted property declaration;
- promoted property constructor-local parameter usage;
- direct class-constant declaration;
- direct class-constant fetch;
- direct enum-case declaration;
- direct enum-case fetch;
- direct function declaration;
- direct function call;
- function namespace move;
- function import update;
- direct namespace-level constant declaration;
- direct namespace-level constant fetch;
- constant namespace move;
- constant import update;
- parameter declaration rename;
- named argument rename;
- local parameter usage rename;
- `@param` docblock rename;
- closure and arrow-function parameter declaration plus local usage rename;
- structured docblock tags such as `@method`, `@property*`, `@mixin`, multi-line `@param`, and function `@see`;
- parent and child method declarations;
- trait method declaration;
- trait alias and precedence adaptations;
- interface method declaration;
- consumers resolved through typed variables;
- unresolved dynamic calls reported as diagnostics.
- conflict policy blocks application when configured as `FAIL`;
- conflict policy reports warnings while allowing application when configured as `REPORT`.
- invalid rename inputs throw before planning;
- no-op renames produce warning diagnostics and empty plans.
- class-like, function, and method conflicts are case-insensitive;
- property, class-constant, enum-case, namespace-level constant, and parameter conflicts are case-sensitive.
- FQCN renames report normal, grouped, and explicit-alias import conflicts, then fall back to fully-qualified usages when application is allowed.
- transactions rebuild an in-memory graph after each successful action.
- transactions restore touched virtual files on rollback.

## Maintenance Rules

- Keep comments and PHPDoc in English.
- Keep public APIs explicit.
- Prefer DTOs and collections over associative arrays.
- Keep `member-graph` analysis concerns out of this package.
- Treat `member-graph` source-node matches as the only rename source of truth.
- Do not add textual search or fallback AST traversal in `php-rename`.
- Keep low-level physical file writing in `php-source-registry`.
- Run PHPStan on both `src` and `tests` before considering a step complete.

Navigation: [Documentation](README.md) | [Previous: AST Application](05-ast-application.md) | [Next: Supported Rename Matrix](07-supported-rename-matrix.md)
