<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Class_;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameNodeApplierInterface;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;

/**
 * Applies class-like owner rename operations to declaration and usage nodes.
 */
final readonly class ClassRenameNodeApplier implements RenameNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::CLASS_ === $operation->symbolKind;
    }

    /**
     * Applies one class-like owner rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): bool
    {
        $node = $operation->node;

        if ($node instanceof ClassLike && $node->name instanceof Identifier) {
            $node->name = $this->replacementIdentifier($node->name, $this->shortName($operation->newName));

            if ($this->isFqcnRename($operation->newName)) {
                return $this->renameDeclarationNamespace($node, $operation->newName, $context);
            }

            return true;
        }

        if ($node instanceof Name) {
            if ($this->isFqcnRename($operation->newName)) {
                return $this->replaceUsageWithFullyQualifiedName($node, $operation, $context);
            }

            $this->renameLastNamePart($node, $operation->newName);

            return true;
        }

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: sprintf('Unsupported class rename node "%s".', $node::class),
        ));

        return false;
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
     * Indicates whether the replacement class name is fully qualified.
     *
     * @param string $name the replacement name
     */
    private function isFqcnRename(string $name): bool
    {
        return str_contains($name, '\\');
    }

    /**
     * Returns the short name for one class-like owner name.
     *
     * @param string $name the class-like owner name
     */
    private function shortName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));

        return (string) end($parts);
    }

    /**
     * Returns the namespace for one class-like owner name.
     *
     * @param string $name the class-like owner name
     */
    private function namespaceName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));
        array_pop($parts);

        return implode('\\', $parts);
    }

    /**
     * Renames the namespace of a matched class-like declaration.
     *
     * @param ClassLike                $classLike the class-like declaration node
     * @param string                   $newName   the replacement fully-qualified class-like owner name
     * @param RenameApplicationContext $context   the rename application context
     */
    private function renameDeclarationNamespace(
        ClassLike $classLike,
        string $newName,
        RenameApplicationContext $context,
    ): bool {
        $namespaceName = $this->namespaceName($newName);
        $parent = $classLike->getAttribute('parent');

        if (!$parent instanceof Namespace_) {
            if ('' === $namespaceName) {
                return true;
            }

            $context->diagnostics->add(new RenameDiagnostic(
                severity: RenameDiagnosticSeverity::WARNING,
                message: 'Cannot move a global class declaration into a namespace without a namespace node.',
            ));

            return false;
        }

        $parent->name = '' === $namespaceName ? null : new Name($namespaceName, $parent->name?->getAttributes() ?? []);

        return true;
    }

    /**
     * Replaces one matched class-like owner usage with a fully-qualified name.
     *
     * @param Name                     $name      the matched usage name node
     * @param RenameOperation          $operation the rename operation
     * @param RenameApplicationContext $context   the rename application context
     */
    private function replaceUsageWithFullyQualifiedName(
        Name $name,
        RenameOperation $operation,
        RenameApplicationContext $context,
    ): bool {
        $namespace = $this->namespaceParent($name);
        $importAlias = null === $namespace ? null : $this->updateOrAddUseImport(
            namespace: $namespace,
            oldName: $operation->oldName,
            newName: $operation->newName,
        );

        if (null !== $importAlias) {
            return $this->replaceUsageWithImportedName($name, $importAlias, $context);
        }

        $replacement = new FullyQualified(ltrim($operation->newName, '\\'), $name->getAttributes());

        if ($this->replaceNodeInParent($name, $replacement)) {
            return true;
        }

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: 'Cannot replace class rename usage without a parent node.',
        ));

        return false;
    }

    /**
     * Replaces one matched usage with a short imported name.
     *
     * @param Name                     $name        the matched usage name node
     * @param string                   $importAlias the imported name alias
     * @param RenameApplicationContext $context     the rename application context
     */
    private function replaceUsageWithImportedName(
        Name $name,
        string $importAlias,
        RenameApplicationContext $context,
    ): bool {
        $replacement = new Name($importAlias, $name->getAttributes());

        if ($this->replaceNodeInParent($name, $replacement)) {
            return true;
        }

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: 'Cannot replace class rename usage without a parent node.',
        ));

        return false;
    }

    /**
     * Updates an existing class import or adds a new one for the replacement owner.
     *
     * @param Namespace_ $namespace the namespace containing the matched usage
     * @param string     $oldName   the old fully-qualified class-like owner name
     * @param string     $newName   the replacement fully-qualified class-like owner name
     */
    private function updateOrAddUseImport(Namespace_ $namespace, string $oldName, string $newName): ?string
    {
        $newNamespaceName = $this->namespaceName($newName);
        $newShortName = $this->shortName($newName);
        $currentNamespaceName = $namespace->name?->toString() ?? '';

        if ($currentNamespaceName === $newNamespaceName) {
            return $newShortName;
        }

        foreach ($namespace->stmts as $statement) {
            $importStatement = $this->normalClassImportStatement($statement);

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
            $importStatement = $this->normalClassImportStatement($statement);

            if (null === $importStatement) {
                continue;
            }

            foreach ($importStatement->uses as $use) {
                if (ltrim($this->importedName($importStatement, $use), '\\') !== ltrim($oldName, '\\')) {
                    continue;
                }

                return $this->updateExistingUseImport($namespace, $importStatement, $use, $newName);
            }
        }

        $this->addUseImport($namespace, $newName);

        return $newShortName;
    }

    /**
     * Updates an existing class import when it can be safely rewritten.
     *
     * @param Namespace_    $namespace the namespace containing the import
     * @param Use_|GroupUse $statement the import statement
     * @param UseItem       $use       the import item to update
     * @param string        $newName   the replacement fully-qualified class-like owner name
     */
    private function updateExistingUseImport(
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

        $this->addUseImportWithAlias($namespace, $newName, $alias);

        return $alias ?? $this->shortName($newName);
    }

    /**
     * Adds a normal class import to a namespace node.
     *
     * @param Namespace_ $namespace the namespace to update
     * @param string     $newName   the fully-qualified class-like owner name to import
     */
    private function addUseImport(Namespace_ $namespace, string $newName): void
    {
        $this->addUseImportWithAlias($namespace, $newName, null);
    }

    /**
     * Adds a normal class import with an optional explicit alias.
     *
     * @param Namespace_  $namespace the namespace to update
     * @param string      $newName   the fully-qualified class-like owner name to import
     * @param string|null $alias     the optional explicit import alias
     */
    private function addUseImportWithAlias(Namespace_ $namespace, string $newName, ?string $alias): void
    {
        $useItem = new UseItem(new Name(ltrim($newName, '\\')));
        $useItem->alias = null === $alias ? null : new Identifier($alias);
        $use = new Use_([$useItem], Use_::TYPE_NORMAL);
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
     * Returns the statement when it declares normal class-like imports.
     *
     * @param Node $statement the statement to inspect
     */
    private function normalClassImportStatement(Node $statement): Use_|GroupUse|null
    {
        if ($statement instanceof Use_) {
            return Use_::TYPE_NORMAL === $statement->type ? $statement : null;
        }

        if (!$statement instanceof GroupUse) {
            return null;
        }

        if (Use_::TYPE_FUNCTION === $statement->type || Use_::TYPE_CONSTANT === $statement->type) {
            return null;
        }

        return $statement;
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

    /**
     * Replaces one node by using its direct parent attribute.
     *
     * @param Node $node        the existing child node
     * @param Node $replacement the replacement child node
     */
    private function replaceNodeInParent(Node $node, Node $replacement): bool
    {
        $parent = $node->getAttribute('parent');

        if (!$parent instanceof Node) {
            return false;
        }

        foreach ($parent->getSubNodeNames() as $subNodeName) {
            $subNode = $parent->{$subNodeName};

            if ($subNode === $node) {
                $replacement->setAttribute('parent', $parent);
                $parent->{$subNodeName} = $replacement;

                return true;
            }

            if (!is_array($subNode)) {
                continue;
            }

            foreach ($subNode as $index => $subNodeItem) {
                if ($subNodeItem !== $node) {
                    continue;
                }

                $replacement->setAttribute('parent', $parent);
                $subNode[$index] = $replacement;
                $parent->{$subNodeName} = $subNode;

                return true;
            }
        }

        return false;
    }

    /**
     * Renames the last part of a name node while preserving its concrete node type and attributes.
     *
     * @param Name   $name    the name node to mutate
     * @param string $newName the replacement short name
     */
    private function renameLastNamePart(Name $name, string $newName): void
    {
        $parts = $name->getParts();
        $parts[array_key_last($parts)] = $newName;
        $name->name = implode('\\', $parts);
    }
}
