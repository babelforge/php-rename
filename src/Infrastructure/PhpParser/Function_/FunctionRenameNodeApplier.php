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
            if ($this->isFqcnRename($operation->newName)) {
                return $this->replaceCallWithImportedName($node, $operation, $context);
            }

            $node->name = $this->replacementName($node->name, $operation->newName);

            return true;
        }

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: sprintf('Unsupported function rename node "%s".', $node::class),
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
     * @param FuncCall                 $call      the matched function call node
     * @param RenameOperation          $operation the rename operation
     * @param RenameApplicationContext $context   the rename application context
     */
    private function replaceCallWithImportedName(
        FuncCall $call,
        RenameOperation $operation,
        RenameApplicationContext $context,
    ): bool {
        $namespace = $this->namespaceParent($call);
        $importAlias = null === $namespace ? null : $this->updateOrAddFunctionUseImport(
            namespace: $namespace,
            oldName: $operation->oldName,
            newName: $operation->newName,
        );

        if (null !== $importAlias) {
            $call->name = new Name($importAlias, $call->name instanceof Name ? $call->name->getAttributes() : []);

            return true;
        }

        $call->name = new FullyQualified(ltrim($operation->newName, '\\'), $call->name instanceof Name ? $call->name->getAttributes() : []);

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
            if (!$statement instanceof Use_ || Use_::TYPE_FUNCTION !== $statement->type) {
                continue;
            }

            foreach ($statement->uses as $use) {
                if (ltrim($use->name->toString(), '\\') === ltrim($newName, '\\')) {
                    return $use->getAlias()->toString();
                }

                if ($use->getAlias()->toString() === $newShortName && ltrim($use->name->toString(), '\\') !== ltrim($oldName, '\\')) {
                    return null;
                }

                if (ltrim($use->name->toString(), '\\') !== ltrim($oldName, '\\')) {
                    continue;
                }

                $use->name = new Name(ltrim($newName, '\\'), $use->name->getAttributes());
                $use->alias = null;

                return $newShortName;
            }
        }

        $this->addFunctionUseImport($namespace, $newName);

        return $newShortName;
    }

    /**
     * Adds a function import to a namespace node.
     *
     * @param Namespace_ $namespace the namespace to update
     * @param string     $newName   the fully-qualified function name to import
     */
    private function addFunctionUseImport(Namespace_ $namespace, string $newName): void
    {
        $useItem = new UseItem(new Name(ltrim($newName, '\\')));
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
     * Creates a replacement name while preserving the original name shape and attributes.
     *
     * @param Name   $name    the original name
     * @param string $newName the replacement short name
     */
    private function replacementName(Name $name, string $newName): Name
    {
        $parts = $name->getParts();
        $parts[array_key_last($parts)] = $newName;

        return new Name($parts, $name->getAttributes());
    }
}
