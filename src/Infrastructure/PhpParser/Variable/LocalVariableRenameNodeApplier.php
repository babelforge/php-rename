<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Variable;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameNodeApplierInterface;
use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr\Variable;

/**
 * Applies local variable rename operations to variable and closure-capture nodes.
 */
final readonly class LocalVariableRenameNodeApplier implements RenameNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::LOCAL_VARIABLE === $operation->symbolKind;
    }

    /**
     * Applies one local variable rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): bool
    {
        $node = $operation->node;

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
            message: sprintf('Unsupported local variable rename node "%s".', $node::class),
        ));

        return false;
    }
}
