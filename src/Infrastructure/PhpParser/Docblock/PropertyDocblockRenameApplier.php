<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Docblock;

use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameMetadataApplierInterface;
use PhpParser\Comment\Doc;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
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
            && ($operation->node instanceof PropertyProperty || $operation->node instanceof Param);
    }

    /**
     * Applies property docblock reference changes for one rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): void
    {
        if ($operation->node instanceof Param) {
            $this->renameNodeDocblock($operation->node, $operation->oldName, $operation->newName);
            $this->renameParentClassDocblock($operation->node, $operation->oldName, $operation->newName);

            return;
        }

        $property = $operation->node->getAttribute('parent');

        if (!$property instanceof Property) {
            return;
        }

        $this->renameNodeDocblock($property, $operation->oldName, $operation->newName);
        $this->renameParentClassDocblock($property, $operation->oldName, $operation->newName);
    }

    /**
     * Renames supported property references on one node docblock.
     *
     * @param Param|Property $node    the node owning the docblock
     * @param string         $oldName the current property name
     * @param string         $newName the replacement property name
     */
    private function renameNodeDocblock(Param|Property $node, string $oldName, string $newName): void
    {
        $docComment = $node->getDocComment();

        if (null === $docComment) {
            return;
        }

        $updatedText = $this->renameSupportedPropertyReferences(
            text: $docComment->getText(),
            oldName: $oldName,
            newName: $newName,
        );

        if ($updatedText === $docComment->getText()) {
            return;
        }

        $node->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
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
            pattern: '/(?:(\b(?:self|static|parent)::\$)'.$quotedOldName.'|(@property(?:-read|-write)?\s+[^\r\n]*\s+\$)'.$quotedOldName.')\b/',
            replacement: '$1$2'.$newName,
            subject: $text,
        ) ?? $text;
    }

    /**
     * Applies property docblock reference changes on the direct class-like parent.
     *
     * @param Param|Property $node    the node used to find the class-like parent
     * @param string         $oldName the current property name
     * @param string         $newName the replacement property name
     */
    private function renameParentClassDocblock(Param|Property $node, string $oldName, string $newName): void
    {
        $parent = $node->getAttribute('parent');

        if ($parent instanceof ClassMethod) {
            $parent = $parent->getAttribute('parent');
        }

        if (!$parent instanceof ClassLike) {
            return;
        }

        $docComment = $parent->getDocComment();

        if (null === $docComment) {
            return;
        }

        $updatedText = $this->renameSupportedPropertyReferences(
            text: $docComment->getText(),
            oldName: $oldName,
            newName: $newName,
        );

        if ($updatedText === $docComment->getText()) {
            return;
        }

        $parent->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
    }
}
