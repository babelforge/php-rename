<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Docblock;

use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameMetadataApplierInterface;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

/**
 * Renames supported property references inside matched property docblocks.
 */
final readonly class PropertyDocblockRenameApplier implements RenameMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::PROPERTY === $operation->symbolKind
            && RenameOperationRole::DECLARATION === $operation->role
            && $operation->node instanceof PropertyProperty;
    }

    /**
     * Applies property docblock reference changes for one rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): void
    {
        $property = $operation->node->getAttribute('parent');

        if (!$property instanceof Property) {
            return;
        }

        $docComment = $property->getDocComment();

        if (null === $docComment) {
            return;
        }

        $updatedText = $this->renameSupportedPropertyReferences(
            text: $docComment->getText(),
            oldName: $operation->oldName,
            newName: $operation->newName,
        );

        if ($updatedText === $docComment->getText()) {
            return;
        }

        $property->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
    }

    /**
     * Renames supported property references inside one docblock text.
     *
     * @param string $text    the docblock text
     * @param string $oldName the current property name
     * @param string $newName the replacement property name
     */
    private function renameSupportedPropertyReferences(string $text, string $oldName, string $newName): string
    {
        $quotedOldName = preg_quote($oldName, '/');

        return preg_replace(
            pattern: '/\b(self|static|parent)::\$'.$quotedOldName.'\b/',
            replacement: '$1::$'.$newName,
            subject: $text,
        ) ?? $text;
    }
}
