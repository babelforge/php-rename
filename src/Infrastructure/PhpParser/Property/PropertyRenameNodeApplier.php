<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Property;

use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameNodeApplierInterface;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\VarLikeIdentifier;

/**
 * Applies property rename operations to property declaration and usage nodes.
 */
final readonly class PropertyRenameNodeApplier implements RenameNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::PROPERTY === $operation->symbolKind;
    }

    /**
     * Applies one property rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): bool
    {
        $node = $operation->node;

        if ($node instanceof PropertyProperty) {
            $node->name = $this->replacementVarLikeIdentifier($node->name, $operation->newName);

            return true;
        }

        if ($node instanceof PropertyFetch && $node->name instanceof Identifier) {
            $node->name = $this->replacementIdentifier($node->name, $operation->newName);

            return true;
        }

        if ($node instanceof StaticPropertyFetch && $node->name instanceof VarLikeIdentifier) {
            $node->name = $this->replacementVarLikeIdentifier($node->name, $operation->newName);

            return true;
        }

        if ($node instanceof Param && $node->var instanceof Variable && is_string($node->var->name)) {
            $node->var->name = $operation->newName;

            return true;
        }

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: sprintf('Unsupported property rename node "%s".', $node::class),
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
     * Creates a replacement variable-like identifier while preserving node attributes.
     *
     * @param VarLikeIdentifier $identifier the original identifier
     * @param string            $name       the replacement name
     */
    private function replacementVarLikeIdentifier(VarLikeIdentifier $identifier, string $name): VarLikeIdentifier
    {
        return new VarLikeIdentifier($name, $identifier->getAttributes());
    }
}
