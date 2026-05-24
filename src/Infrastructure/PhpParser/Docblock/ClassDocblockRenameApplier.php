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

/**
 * Renames supported class-like owner references inside matched declaration docblocks.
 */
final readonly class ClassDocblockRenameApplier implements RenameMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::CLASS_ === $operation->symbolKind
            && RenameOperationRole::DECLARATION === $operation->role
            && $operation->node instanceof ClassLike;
    }

    /**
     * Applies class-like owner docblock reference changes for one rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): void
    {
        if (!$operation->node instanceof ClassLike) {
            return;
        }

        $docComment = $operation->node->getDocComment();

        if (null === $docComment) {
            return;
        }

        $updatedText = $this->renameSupportedClassReferences(
            text: $docComment->getText(),
            oldName: $operation->oldName,
            newName: $operation->newName,
        );

        if ($updatedText === $docComment->getText()) {
            return;
        }

        $operation->node->setDocComment(new Doc($updatedText, $docComment->getStartLine(), $docComment->getStartFilePos()));
    }

    /**
     * Renames supported class-like owner references inside one docblock text.
     *
     * @param string $text    the docblock text
     * @param string $oldName the current class-like owner short name
     * @param string $newName the replacement class-like owner short name
     */
    private function renameSupportedClassReferences(string $text, string $oldName, string $newName): string
    {
        $updatedText = $this->renameSupportedClassName($text, $oldName, $newName);

        if (!str_contains($oldName, '\\')) {
            return $updatedText;
        }

        return $this->renameSupportedClassName(
            text: $updatedText,
            oldName: $this->shortName($oldName),
            newName: $this->shortName($newName),
        );
    }

    /**
     * Renames one supported class-like owner name form inside one docblock text.
     *
     * @param string $text    the docblock text
     * @param string $oldName the current class-like owner name form
     * @param string $newName the replacement class-like owner name form
     */
    private function renameSupportedClassName(string $text, string $oldName, string $newName): string
    {
        $quotedOldName = preg_quote(ltrim($oldName, '\\'), '/');

        return preg_replace(
            pattern: '/(@(?:see|var|param|return|throws|extends|implements|template|mixin|property(?:-read|-write)?|method)\s+[^\r\n]*)\b'.$quotedOldName.'\b/',
            replacement: '$1'.ltrim($newName, '\\'),
            subject: $text,
        ) ?? $text;
    }

    /**
     * Returns the short name for one class-like owner name.
     *
     * @param string $name the class-like owner name
     */
    private function shortName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));

        return (string) end($parts);
    }
}
