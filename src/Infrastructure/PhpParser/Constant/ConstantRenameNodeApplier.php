<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Constant;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameNodeApplierInterface;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;

/**
 * Applies namespace-level constant rename operations to declaration and usage nodes.
 */
final readonly class ConstantRenameNodeApplier implements RenameNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::CONSTANT === $operation->symbolKind;
    }

    /**
     * Applies one namespace-level constant rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): bool
    {
        $node = $operation->node;
        $replacementName = $this->resolvedReplacementName($operation);

        if ($node instanceof Const_) {
            $node->name = $this->replacementIdentifier($node->name, $this->shortName($replacementName));

            if ($this->isFqcnRename($operation->newName)) {
                return $this->renameDeclarationNamespace($node, $replacementName, $context);
            }

            return true;
        }

        if ($node instanceof ConstFetch) {
            return $this->replaceFetchWithImportedName($node, $operation, $replacementName, $context);
        }

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: sprintf('Unsupported constant rename node "%s".', $node::class),
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
     * Indicates whether the replacement constant name is fully qualified.
     *
     * @param string $name the replacement name
     */
    private function isFqcnRename(string $name): bool
    {
        return str_contains($name, '\\');
    }

    /**
     * Returns the short name for one constant name.
     *
     * @param string $name the constant name
     */
    private function shortName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));

        return (string) end($parts);
    }

    /**
     * Returns the namespace for one constant name.
     *
     * @param string $name the constant name
     */
    private function namespaceName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));
        array_pop($parts);

        return implode('\\', $parts);
    }

    /**
     * Renames the namespace of a matched constant declaration.
     *
     * @param Const_                   $constant the constant declaration node
     * @param string                   $newName  the replacement fully-qualified constant name
     * @param RenameApplicationContext $context  the rename application context
     */
    private function renameDeclarationNamespace(
        Const_ $constant,
        string $newName,
        RenameApplicationContext $context,
    ): bool {
        $namespaceName = $this->namespaceName($newName);
        $parent = $this->namespaceParent($constant);

        if (!$parent instanceof Namespace_) {
            if ('' === $namespaceName) {
                return true;
            }

            $context->diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'Cannot move a global constant declaration into a namespace without a namespace node.',
            ));

            return false;
        }

        $parent->name = '' === $namespaceName ? null : new Name($namespaceName, $parent->name?->getAttributes() ?? []);

        return true;
    }

    /**
     * Replaces one matched constant fetch with a short imported constant name.
     *
     * @param ConstFetch               $fetch           the matched constant fetch node
     * @param RenameOperation          $operation       the rename operation
     * @param string                   $replacementName the fully-qualified replacement constant name
     * @param RenameApplicationContext $context         the rename application context
     */
    private function replaceFetchWithImportedName(
        ConstFetch $fetch,
        RenameOperation $operation,
        string $replacementName,
        RenameApplicationContext $context,
    ): bool {
        $namespace = $this->namespaceParent($fetch);
        $importAlias = null === $namespace ? null : $this->updateOrAddConstantUseImport(
            namespace: $namespace,
            oldName: $operation->oldName,
            newName: $replacementName,
        );

        if (null !== $importAlias) {
            $fetch->name = new Name($importAlias, $fetch->name->getAttributes());

            return true;
        }

        $fetch->name = new FullyQualified(ltrim($replacementName, '\\'), $fetch->name->getAttributes());

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: 'Constant rename fell back to a fully-qualified fetch name because a constant import could not be added safely.',
        ));

        return true;
    }

    /**
     * Updates an existing constant import or adds a new one for the replacement constant.
     *
     * @param Namespace_ $namespace the namespace containing the matched fetch
     * @param string     $oldName   the old fully-qualified constant name
     * @param string     $newName   the replacement fully-qualified constant name
     */
    private function updateOrAddConstantUseImport(Namespace_ $namespace, string $oldName, string $newName): ?string
    {
        $newNamespaceName = $this->namespaceName($newName);
        $newShortName = $this->shortName($newName);
        $currentNamespaceName = $namespace->name?->toString() ?? '';

        if ($currentNamespaceName === $newNamespaceName) {
            return $newShortName;
        }

        foreach ($namespace->stmts as $statement) {
            $importStatement = $this->constantImportStatement($statement);

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
            $importStatement = $this->constantImportStatement($statement);

            if (null === $importStatement) {
                continue;
            }

            foreach ($importStatement->uses as $use) {
                if (ltrim($this->importedName($importStatement, $use), '\\') !== ltrim($oldName, '\\')) {
                    continue;
                }

                return $this->updateExistingConstantUseImport($namespace, $importStatement, $use, $newName);
            }
        }

        $this->addConstantUseImport($namespace, $newName);

        return $newShortName;
    }

    /**
     * Updates an existing constant import when it can be safely rewritten.
     *
     * @param Namespace_    $namespace the namespace containing the import
     * @param Use_|GroupUse $statement the import statement
     * @param UseItem       $use       the import item to update
     * @param string        $newName   the replacement fully-qualified constant name
     */
    private function updateExistingConstantUseImport(
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

        $this->addConstantUseImportWithAlias($namespace, $newName, $alias);

        return $alias ?? $this->shortName($newName);
    }

    /**
     * Adds a constant import to a namespace node.
     *
     * @param Namespace_ $namespace the namespace to update
     * @param string     $newName   the fully-qualified constant name to import
     */
    private function addConstantUseImport(Namespace_ $namespace, string $newName): void
    {
        $this->addConstantUseImportWithAlias($namespace, $newName, null);
    }

    /**
     * Adds a constant import with an optional explicit alias.
     *
     * @param Namespace_  $namespace the namespace to update
     * @param string      $newName   the fully-qualified constant name to import
     * @param string|null $alias     the optional explicit import alias
     */
    private function addConstantUseImportWithAlias(Namespace_ $namespace, string $newName, ?string $alias): void
    {
        $useItem = new UseItem(new Name(ltrim($newName, '\\')));
        $useItem->alias = null === $alias ? null : new Identifier($alias);
        $use = new Use_([$useItem], Use_::TYPE_CONSTANT);
        $useItem->setAttribute('parent', $use);
        $use->setAttribute('parent', $namespace);
        $insertAt = 0;

        foreach ($namespace->stmts as $index => $statement) {
            if ($statement instanceof Use_ || $statement instanceof GroupUse) {
                $insertAt = $index + 1;
            }
        }

        array_splice($namespace->stmts, $insertAt, 0, [$use]);
    }

    /**
     * Returns the statement when it declares constant imports.
     *
     * @param Node $statement the statement to inspect
     */
    private function constantImportStatement(Node $statement): Use_|GroupUse|null
    {
        if ($statement instanceof Use_) {
            return Use_::TYPE_CONSTANT === $statement->type ? $statement : null;
        }

        if (!$statement instanceof GroupUse) {
            return null;
        }

        return Use_::TYPE_CONSTANT === $statement->type ? $statement : null;
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
