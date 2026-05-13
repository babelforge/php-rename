<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Function_;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameNodeApplierInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;

/**
 * Applies function rename operations to function declaration and usage nodes.
 */
final readonly class FunctionRenameNodeApplier implements RenameNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::FUNCTION_ === $operation->symbolKind;
    }

    /**
     * Applies one function rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): bool
    {
        $node = $operation->node;

        if ($node instanceof Function_) {
            $node->name = $this->replacementIdentifier($node->name, $this->shortName($operation->newName));

            if ($this->isFqcnRename($operation->newName)) {
                return $this->renameDeclarationNamespace($node, $operation->newName, $context);
            }

            return true;
        }

        if ($node instanceof FuncCall && $node->name instanceof Name) {
            return $this->replaceCallWithImportedName(
                call: $node,
                operation: $operation,
                replacementName: $this->resolvedReplacementName($operation),
                context: $context,
            );
        }

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: sprintf('Unsupported function rename node "%s".', $node::class),
        ));

        return false;
    }

    /**
     * Returns the fully-qualified replacement name represented by one operation.
     *
     * @param RenameOperation $operation the rename operation
     */
    private function resolvedReplacementName(RenameOperation $operation): string
    {
        if ($this->isFqcnRename($operation->newName)) {
            return ltrim($operation->newName, '\\');
        }

        $namespaceName = $this->namespaceName($operation->oldName);

        return '' === $namespaceName ? $operation->newName : $namespaceName.'\\'.$operation->newName;
    }

    /**
     * Creates a replacement identifier while preserving node attributes.
     *
     * @param Identifier $identifier the original identifier
     * @param string     $name       the replacement name
     */
    private function replacementIdentifier(Identifier $identifier, string $name): Identifier
    {
        return new Identifier($name, $identifier->getAttributes());
    }

    /**
     * Indicates whether the replacement function name is fully qualified.
     *
     * @param string $name the replacement name
     */
    private function isFqcnRename(string $name): bool
    {
        return str_contains($name, '\\');
    }

    /**
     * Returns the short name for one function name.
     *
     * @param string $name the function name
     */
    private function shortName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));

        return (string) end($parts);
    }

    /**
     * Returns the namespace for one function name.
     *
     * @param string $name the function name
     */
    private function namespaceName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));
        array_pop($parts);

        return implode('\\', $parts);
    }

    /**
     * Renames the namespace of a matched function declaration.
     *
     * @param Function_                $function the function declaration node
     * @param string                   $newName  the replacement fully-qualified function name
     * @param RenameApplicationContext $context  the rename application context
     */
    private function renameDeclarationNamespace(
        Function_ $function,
        string $newName,
        RenameApplicationContext $context,
    ): bool {
        $namespaceName = $this->namespaceName($newName);
        $parent = $function->getAttribute('parent');

        if (!$parent instanceof Namespace_) {
            if ('' === $namespaceName) {
                return true;
            }

            $context->diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'Cannot move a global function declaration into a namespace without a namespace node.',
            ));

            return false;
        }

        $parent->name = '' === $namespaceName ? null : new Name($namespaceName, $parent->name?->getAttributes() ?? []);

        return true;
    }

    /**
     * Replaces one matched function call with a short imported function name.
     *
     * @param FuncCall                 $call            the matched function call node
     * @param RenameOperation          $operation       the rename operation
     * @param string                   $replacementName the fully-qualified replacement function name
     * @param RenameApplicationContext $context         the rename application context
     */
    private function replaceCallWithImportedName(
        FuncCall $call,
        RenameOperation $operation,
        string $replacementName,
        RenameApplicationContext $context,
    ): bool {
        $namespace = $this->namespaceParent($call);
        $importAlias = null === $namespace ? null : $this->updateOrAddFunctionUseImport(
            namespace: $namespace,
            oldName: $operation->oldName,
            newName: $replacementName,
        );

        if (null !== $importAlias) {
            $call->name = new Name($importAlias, $call->name instanceof Name ? $call->name->getAttributes() : []);

            return true;
        }

        $call->name = new FullyQualified(ltrim($replacementName, '\\'), $call->name instanceof Name ? $call->name->getAttributes() : []);

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: 'Function FQCN rename fell back to a fully-qualified call name because a function import could not be added safely.',
        ));

        return true;
    }

    /**
     * Updates an existing function import or adds a new one for the replacement function.
     *
     * @param Namespace_ $namespace the namespace containing the matched call
     * @param string     $oldName   the old fully-qualified function name
     * @param string     $newName   the replacement fully-qualified function name
     */
    private function updateOrAddFunctionUseImport(Namespace_ $namespace, string $oldName, string $newName): ?string
    {
        $newNamespaceName = $this->namespaceName($newName);
        $newShortName = $this->shortName($newName);
        $currentNamespaceName = $namespace->name?->toString() ?? '';

        if ($currentNamespaceName === $newNamespaceName) {
            return $newShortName;
        }

        foreach ($namespace->stmts as $statement) {
            $importStatement = $this->functionImportStatement($statement);

            if (null === $importStatement) {
                continue;
            }

            foreach ($importStatement->uses as $use) {
                $importedName = $this->importedName($importStatement, $use);

                if (ltrim($importedName, '\\') === ltrim($newName, '\\')) {
                    return $use->getAlias()->toString();
                }

                if ($use->getAlias()->toString() === $newShortName && ltrim($importedName, '\\') !== ltrim($oldName, '\\')) {
                    return null;
                }
            }
        }

        foreach ($namespace->stmts as $statement) {
            $importStatement = $this->functionImportStatement($statement);

            if (null === $importStatement) {
                continue;
            }

            foreach ($importStatement->uses as $use) {
                if (ltrim($this->importedName($importStatement, $use), '\\') !== ltrim($oldName, '\\')) {
                    continue;
                }

                return $this->updateExistingFunctionUseImport($namespace, $importStatement, $use, $newName);
            }
        }

        $this->addFunctionUseImport($namespace, $newName);

        return $newShortName;
    }

    /**
     * Updates an existing function import when it can be safely rewritten.
     *
     * @param Namespace_    $namespace the namespace containing the import
     * @param Use_|GroupUse $statement the import statement
     * @param UseItem       $use       the import item to update
     * @param string        $newName   the replacement fully-qualified function name
     */
    private function updateExistingFunctionUseImport(
        Namespace_ $namespace,
        Use_|GroupUse $statement,
        UseItem $use,
        string $newName,
    ): string {
        $alias = $use->alias?->toString();

        if (!$statement instanceof GroupUse) {
            $use->name = new Name(ltrim($newName, '\\'), $use->name->getAttributes());

            return $alias ?? $this->shortName($newName);
        }

        if ($statement->prefix->toString() === $this->namespaceName($newName)) {
            $use->name = new Name($this->shortName($newName), $use->name->getAttributes());

            return $alias ?? $this->shortName($newName);
        }

        $this->addFunctionUseImportWithAlias($namespace, $newName, $alias);

        return $alias ?? $this->shortName($newName);
    }

    /**
     * Adds a function import to a namespace node.
     *
     * @param Namespace_ $namespace the namespace to update
     * @param string     $newName   the fully-qualified function name to import
     */
    private function addFunctionUseImport(Namespace_ $namespace, string $newName): void
    {
        $this->addFunctionUseImportWithAlias($namespace, $newName, null);
    }

    /**
     * Adds a function import with an optional explicit alias.
     *
     * @param Namespace_  $namespace the namespace to update
     * @param string      $newName   the fully-qualified function name to import
     * @param string|null $alias     the optional explicit import alias
     */
    private function addFunctionUseImportWithAlias(Namespace_ $namespace, string $newName, ?string $alias): void
    {
        $useItem = new UseItem(new Name(ltrim($newName, '\\')));
        $useItem->alias = null === $alias ? null : new Identifier($alias);
        $use = new Use_([$useItem], Use_::TYPE_FUNCTION);
        $useItem->setAttribute('parent', $use);
        $use->setAttribute('parent', $namespace);
        $insertAt = 0;

        foreach ($namespace->stmts as $index => $statement) {
            if ($statement instanceof Use_) {
                $insertAt = $index + 1;
            }
        }

        array_splice($namespace->stmts, $insertAt, 0, [$use]);
    }

    /**
     * Returns the statement when it declares function imports.
     *
     * @param Node $statement the statement to inspect
     */
    private function functionImportStatement(Node $statement): Use_|GroupUse|null
    {
        if ($statement instanceof Use_) {
            return Use_::TYPE_FUNCTION === $statement->type ? $statement : null;
        }

        if (!$statement instanceof GroupUse) {
            return null;
        }

        return Use_::TYPE_FUNCTION === $statement->type ? $statement : null;
    }

    /**
     * Returns the fully-qualified imported name represented by one use item.
     *
     * @param Use_|GroupUse $statement the import statement
     * @param UseItem       $use       the import item
     */
    private function importedName(Use_|GroupUse $statement, UseItem $use): string
    {
        if (!$statement instanceof GroupUse) {
            return $use->name->toString();
        }

        return $statement->prefix->toString().'\\'.$use->name->toString();
    }

    /**
     * Returns the nearest namespace parent for one node.
     *
     * @param Node $node the node used as the starting point
     */
    private function namespaceParent(Node $node): ?Namespace_
    {
        $parent = $node->getAttribute('parent');

        while ($parent instanceof Node) {
            if ($parent instanceof Namespace_) {
                return $parent;
            }

            $parent = $parent->getAttribute('parent');
        }

        return null;
    }
}
