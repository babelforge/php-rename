<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\ClassConstant;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameNodeApplierInterface;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\EnumCase;

/**
 * Applies class-constant rename operations to class-constant declaration and usage nodes.
 */
final readonly class ClassConstantRenameNodeApplier implements RenameNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::CLASS_CONSTANT === $operation->symbolKind
            || RenameSymbolKind::ENUM_CASE === $operation->symbolKind;
    }

    /**
     * Applies one class-constant rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): bool
    {
        $node = $operation->node;

        if ($node instanceof Const_) {
            $node->name = $this->replacementIdentifier($node->name, $operation->newName);

            return true;
        }

        if ($node instanceof EnumCase) {
            $node->name = $this->replacementIdentifier($node->name, $operation->newName);

            return true;
        }

        if ($node instanceof ClassConstFetch && $node->name instanceof Identifier) {
            $node->name = $this->replacementIdentifier($node->name, $operation->newName);

            return true;
        }

        $context->diagnostics->add(new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: sprintf('Unsupported class-constant or enum-case rename node "%s".', $node::class),
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
}
