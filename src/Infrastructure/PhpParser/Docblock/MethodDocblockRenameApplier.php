<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Infrastructure\PhpParser\Docblock;

use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperation;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use BabelForge\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use BabelForge\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use BabelForge\PhpRename\Infrastructure\PhpParser\Application\RenameMetadataApplierInterface;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Renames supported method references inside matched method docblocks.
 */
final readonly class MethodDocblockRenameApplier implements RenameMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::METHOD === $operation->symbolKind
            && RenameOperationRole::DECLARATION === $operation->role
            && $operation->node instanceof ClassMethod;
    }

    /**
     * Applies method docblock reference changes for one rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): void
    {
        if (!$operation->node instanceof ClassMethod) {
            return;
        }

        $docComment = $operation->node->getDocComment();

        if (null === $docComment) {
            $this->renameParentClassDocblock($operation);

            return;
        }

        $updatedText = $this->renameSupportedMethodReferences(
            text: $docComment->getText(),
            oldName: $operation->oldName,
            newName: $operation->newName,
        );

        if ($updatedText === $docComment->getText()) {
            $this->renameParentClassDocblock($operation);

            return;
        }

        $operation->node->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
        $this->renameParentClassDocblock($operation);
    }

    /**
     * Applies method docblock reference changes on the direct class-like parent.
     *
     * @param RenameOperation $operation the rename operation to apply
     */
    private function renameParentClassDocblock(RenameOperation $operation): void
    {
        if (!$operation->node instanceof ClassMethod) {
            return;
        }

        $classLike = $operation->node->getAttribute('parent');

        if (!$classLike instanceof ClassLike) {
            return;
        }

        $docComment = $classLike->getDocComment();

        if (null === $docComment) {
            return;
        }

        $updatedText = $this->renameSupportedMethodReferences(
            text: $docComment->getText(),
            oldName: $operation->oldName,
            newName: $operation->newName,
        );

        if ($updatedText === $docComment->getText()) {
            return;
        }

        $classLike->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
    }

    /**
     * Renames supported method references inside one docblock text.
     *
     * @param string $text    the docblock text
     * @param string $oldName the current method name
     * @param string $newName the replacement method name
     */
    private function renameSupportedMethodReferences(string $text, string $oldName, string $newName): string
    {
        $quotedOldName = preg_quote($oldName, '/');

        return preg_replace(
            pattern: '/(?:(\b(?:self|static|parent)::)'.$quotedOldName.'|(@method\s+[^\r\n]*\s+)'.$quotedOldName.')(?=\s*\()/',
            replacement: '$1$2'.$newName,
            subject: $text,
        ) ?? $text;
    }
}
