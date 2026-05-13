# Supported Rename Matrix

Navigation: [Documentation](README.md) | [Previous: Testing And Maintenance](06-testing-and-maintenance.md)

This page records what `php-rename` supports and where the package boundary stops.

## Matrix

| Symbol kind | Declarations | Usages | Imports | Docblocks | Conflicts | Known limitations |
| --- | --- | --- | --- | --- | --- | --- |
| Class-like short rename | Class-like declarations returned by `member-graph` | Class-like `Name` usages returned by `member-graph` | Not needed for same-namespace short rename | Structured class-like tags on matched declaration docblocks | Target namespace class-like declarations | Does not move namespace or physical file path |
| Class-like FQCN rename | Class-like declarations and direct namespace parent | Class-like `Name` usages returned by `member-graph` | Normal class-like `use` imports, with fully-qualified fallback on alias conflict | Structured class-like tags on matched declaration docblocks, including FQCN references | Target namespace declarations and class-like import aliases | Does not move physical file path |
| Method rename | Method declarations returned by `member-graph`, including semantic owner projections | Method calls and trait adaptation references returned by `member-graph` | Not applicable | Supported method references and `@method` tags on matched/direct-owner docblocks | Resolved owner-scope method declarations | Dynamic or unresolved calls depend on `member-graph` facts |
| Property rename | Property declarations, promoted-property declarations, and property fetches returned by `member-graph` | Instance/static property fetches and promoted constructor-local variable usages returned by `member-graph` | Not applicable | Supported property references and `@property*` tags on matched/direct-owner docblocks | Resolved owner-scope property declarations | Local promoted-property usages are limited to facts exposed by `member-graph` |
| Class constant rename | Class constant declarations returned by `member-graph` | Class constant fetches returned by `member-graph` | Not applicable | Supported `self::`, `static::`, and `parent::` constant references on matched/direct-owner docblocks | Resolved owner-scope class constants and enum cases | Does not rewrite unrelated comments or free text |
| Enum case rename | Enum case declarations returned through the class-constant locator path | Enum case fetches returned by `member-graph` | Not applicable | Supported enum-case references on matched docblocks | Resolved owner-scope class constants and enum cases | Shares source-node lookup with class constants |
| Function short rename | Function declarations and calls returned by `member-graph` | Function calls returned by `member-graph` | Existing `use function` imports are rewritten to the renamed FQCN | Structured function `@see` references on matched declaration docblocks | Target namespace function declarations | Does not move namespace |
| Function FQCN rename | Function declarations and direct namespace parent | Function calls returned by `member-graph` | `use function` imports, with fully-qualified fallback on alias conflict | Structured function `@see` references, including FQCN references | Target namespace function declarations and function import aliases | Does not move physical file path |
| Namespace-level constant short rename | Namespace-level `Const_` declarations returned by `member-graph` | Constant fetches returned by `member-graph` | Existing `use const` imports are rewritten to the renamed FQCN | Structured constant `@see` references on matched declaration docblocks | Target namespace constant declarations and constant import aliases | Does not move namespace |
| Namespace-level constant FQCN rename | Namespace-level `Const_` declarations and direct namespace parent | Constant fetches returned by `member-graph` | `use const` imports, with fully-qualified fallback on alias conflict | Structured constant `@see` references, including FQCN references | Target namespace constant declarations and constant import aliases | Does not move physical file path |
| Parameter rename | `Param` declarations returned by `member-graph` | Named arguments and local parameter variable usages returned by `member-graph` | Not applicable | Supported `@param` tags on direct function-like parent docblocks | Same-signature parameters and local variables in the declaring body | Local usage coverage follows `member-graph` parameter-scope facts |
| Nested callable parameter rename | Selected closure or arrow-function `Param` declaration inside an explicit method, function, or file container | Local variable usages inside the selected callable body, explicit closure captures, and implicit arrow-function captures | Not applicable | Supported `@param` tags on the callable or parent-chain docblock | Same-callable parameters and local variables | Callable selection uses zero-based DFS index inside the explicit container; shadowed nested callable parameters are skipped |

## Explicit Non-Goals

Namespace-wide rename is not a supported first-class operation in `php-rename`. It belongs to an orchestration layer that can combine multiple symbol renames, import rewrites, path moves, and cache updates.

Physical path moves are not performed by `php-rename`. FQCN renames mutate AST namespace declarations and usages in virtual files only.

`php-rename` does not discover rename targets by text search or project-wide semantic traversal. It mutates nodes returned by `member-graph`, nodes inside an explicit selected nested callable container, and metadata attached to those matched nodes or structural owners.

Navigation: [Documentation](README.md) | [Previous: Testing And Maintenance](06-testing-and-maintenance.md)
