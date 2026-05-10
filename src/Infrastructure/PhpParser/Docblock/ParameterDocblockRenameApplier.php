<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Docblock;

use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameMetadataApplierInterface;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

/**
 * Renames supported parameter references inside the matched function-like declaration docblock.
 */
final readonly class ParameterDocblockRenameApplier implements RenameMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::PARAMETER === $operation->symbolKind
            && RenameOperationRole::DECLARATION === $operation->role
            && $operation->node instanceof Param;
    }

    /**
     * Applies parameter docblock changes for one rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): void
    {
        if (!$operation->node instanceof Param) {
            return;
        }

        $functionLike = $this->functionLikeParent($operation->node);

        if (null === $functionLike) {
            return;
        }

        $docComment = $functionLike->getDocComment();

        if (null === $docComment) {
            return;
        }

        $updatedText = $this->renameParamTag(
            text: $docComment->getText(),
            oldName: $operation->oldName,
            newName: $operation->newName,
        );

        if ($updatedText === $docComment->getText()) {
            return;
        }

        $functionLike->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
    }

    /**
     * Returns the function-like parent for one parameter declaration.
     *
     * @param Param $parameter the parameter declaration node
     */
    private function functionLikeParent(Param $parameter): ClassMethod|Function_|null
    {
        $parent = $parameter->getAttribute('parent');

        if ($parent instanceof ClassMethod || $parent instanceof Function_) {
            return $parent;
        }

        return null;
    }

    /**
     * Renames one parameter name inside supported `@param` tags.
     *
     * @param string $text    the docblock text
     * @param string $oldName the current parameter name without "$"
     * @param string $newName the replacement parameter name without "$"
     */
    private function renameParamTag(string $text, string $oldName, string $newName): string
    {
        $quotedOldName = preg_quote($oldName, '/');

        return preg_replace(
            pattern: '/(@param\s+[^\r\n]*\s+)\$'.$quotedOldName.'\b/',
            replacement: '$1$'.$newName,
            subject: $text,
        ) ?? $text;
    }
}
