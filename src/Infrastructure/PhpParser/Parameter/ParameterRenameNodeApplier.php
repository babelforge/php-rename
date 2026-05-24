<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Infrastructure\PhpParser\Parameter;

use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperation;
use BabelForge\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use BabelForge\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use BabelForge\PhpRename\Infrastructure\PhpParser\Application\RenameNodeApplierInterface;
use PhpParser\Node\Arg;
use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;

/**
 * Applies parameter rename operations to declaration, named-argument, and local usage nodes.
 */
final readonly class ParameterRenameNodeApplier implements RenameNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::PARAMETER === $operation->symbolKind;
    }

    /**
     * Applies one parameter rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): bool
    {
        $node = $operation->node;

        if ($node instanceof Param && $node->var instanceof Variable && is_string($node->var->name)) {
            $node->var->name = $operation->newName;

            return true;
        }

        if ($node instanceof Arg && null !== $node->name) {
            $node->name = new Identifier($operation->newName, $node->name->getAttributes());

            return true;
        }

        if ($node instanceof Variable && is_string($node->name)) {
            $node->name = $operation->newName;

            return true;
        }

        if ($node instanceof ClosureUse && is_string($node->var->name)) {
            $node->var->name = $operation->newName;

            return true;
        }

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: sprintf('Unsupported parameter rename node "%s".', $node::class),
        ));

        return false;
    }
}
