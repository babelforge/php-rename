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

        foreach ($this->docblockOwners($operation->node) as $docblockOwner) {
            $docComment = $docblockOwner->getDocComment();

            if (null === $docComment) {
                continue;
            }

            $updatedText = $this->renameParamTag(
                text: $docComment->getText(),
                oldName: $operation->oldName,
                newName: $operation->newName,
            );

            if ($updatedText === $docComment->getText()) {
                continue;
            }

            $docblockOwner->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));

            return;
        }
    }

    /**
     * Returns candidate docblock owners from the parameter parent chain.
     *
     * @param Param $parameter the parameter declaration node
     *
     * @return list<Node>
     */
    private function docblockOwners(Param $parameter): array
    {
        $owners = [];
        $parent = $parameter->getAttribute('parent');

        while ($parent instanceof Node) {
            $owners[] = $parent;
            $parent = $parent->getAttribute('parent');
        }

        return $owners;
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
            pattern: '/(@param\b(?:(?!\R\s*\*\s*@).)*?)\$'.$quotedOldName.'\b/s',
            replacement: '$1$'.$newName,
            subject: $text,
        ) ?? $text;
    }
}
