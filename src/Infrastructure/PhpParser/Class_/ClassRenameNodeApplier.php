<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Class_;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameNodeApplierInterface;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;

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
            $node->name = $this->replacementIdentifier($node->name, $operation->newName);

            return true;
        }

        if ($node instanceof Name) {
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
