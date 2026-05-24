<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Infrastructure\PhpParser\Docblock;

use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperation;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use BabelForge\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use BabelForge\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use BabelForge\PhpRename\Infrastructure\PhpParser\Application\RenameMetadataApplierInterface;
use PhpParser\Comment\Doc;
use PhpParser\Node\Const_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\EnumCase;

/**
 * Renames supported class-constant references inside matched declaration docblocks.
 */
final readonly class ClassConstantDocblockRenameApplier implements RenameMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return (RenameSymbolKind::CLASS_CONSTANT === $operation->symbolKind || RenameSymbolKind::ENUM_CASE === $operation->symbolKind)
            && RenameOperationRole::DECLARATION === $operation->role
            && ($operation->node instanceof Const_ || $operation->node instanceof EnumCase);
    }

    /**
     * Applies class-constant docblock reference changes for one rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): void
    {
        $docblockOwner = $this->docblockOwner($operation);

        if (null === $docblockOwner) {
            return;
        }

        $docComment = $docblockOwner->getDocComment();

        if (null === $docComment) {
            return;
        }

        $updatedText = $this->renameSupportedClassConstantReferences(
            text: $docComment->getText(),
            oldName: $operation->oldName,
            newName: $operation->newName,
        );

        if ($updatedText === $docComment->getText()) {
            return;
        }

        $docblockOwner->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
    }

    /**
     * Returns the node that owns the docblock for one class-constant declaration operation.
     *
     * @param RenameOperation $operation the rename operation
     */
    private function docblockOwner(RenameOperation $operation): ClassConst|EnumCase|null
    {
        if ($operation->node instanceof EnumCase) {
            return $operation->node;
        }

        $classConst = $operation->node->getAttribute('parent');

        if ($classConst instanceof ClassConst) {
            return $classConst;
        }

        return null;
    }

    /**
     * Renames supported class-constant references inside one docblock text.
     *
     * @param string $text    the docblock text
     * @param string $oldName the current class-constant name
     * @param string $newName the replacement class-constant name
     */
    private function renameSupportedClassConstantReferences(string $text, string $oldName, string $newName): string
    {
        $quotedOldName = preg_quote($oldName, '/');

        return preg_replace(
            pattern: '/\b(self|static|parent)::'.$quotedOldName.'\b/',
            replacement: '$1::'.$newName,
            subject: $text,
        ) ?? $text;
    }
}
