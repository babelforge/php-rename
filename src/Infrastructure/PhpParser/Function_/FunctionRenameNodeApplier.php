<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Function_;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameNodeApplierInterface;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Function_;

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
            $node->name = $this->replacementIdentifier($node->name, $operation->newName);

            return true;
        }

        if ($node instanceof FuncCall && $node->name instanceof Name) {
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
