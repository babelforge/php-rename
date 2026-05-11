<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Docblock;

use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperation;
use PhpNoobs\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use PhpNoobs\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use PhpNoobs\PhpRename\Infrastructure\PhpParser\Application\RenameMetadataApplierInterface;
use PhpParser\Comment\Doc;
use PhpParser\Node\Const_;
use PhpParser\Node\Stmt;

/**
 * Renames supported namespace-level constant references inside matched declaration docblocks.
 */
final readonly class ConstantDocblockRenameApplier implements RenameMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::CONSTANT === $operation->symbolKind
            && RenameOperationRole::DECLARATION === $operation->role
            && $operation->node instanceof Const_;
    }

    /**
     * Applies namespace-level constant docblock reference changes for one rename operation.
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

        $updatedText = $this->renameSupportedConstantReferences(
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
     * Returns the statement that owns the docblock for one namespace-level constant declaration.
     *
     * @param RenameOperation $operation the rename operation
     */
    private function docblockOwner(RenameOperation $operation): ?Stmt\Const_
    {
        $parent = $operation->node->getAttribute('parent');

        if ($parent instanceof Stmt\Const_) {
            return $parent;
        }

        return null;
    }

    /**
     * Renames supported namespace-level constant references inside one docblock text.
     *
     * @param string $text    the docblock text
     * @param string $oldName the current namespace-level constant name
     * @param string $newName the replacement namespace-level constant name
     */
    private function renameSupportedConstantReferences(string $text, string $oldName, string $newName): string
    {
        $updatedText = $this->renameSupportedConstantName(
            text: $text,
            oldName: ltrim($oldName, '\\'),
            newName: $this->fullyQualifiedReplacementName($oldName, $newName),
        );

        return $this->renameSupportedConstantName(
            text: $updatedText,
            oldName: $this->shortName($oldName),
            newName: $this->shortName($newName),
        );
    }

    /**
     * Renames one supported constant name form inside one docblock text.
     *
     * @param string $text    the docblock text
     * @param string $oldName the current constant name form
     * @param string $newName the replacement constant name form
     */
    private function renameSupportedConstantName(string $text, string $oldName, string $newName): string
    {
        $quotedOldName = preg_quote(ltrim($oldName, '\\'), '/');

        return preg_replace(
            pattern: '/(@see\s+)\\\\?'.$quotedOldName.'\b/',
            replacement: '$1'.ltrim($newName, '\\'),
            subject: $text,
        ) ?? $text;
    }

    /**
     * Returns the fully-qualified replacement name for one constant rename.
     *
     * @param string $oldName the current namespace-level constant name
     * @param string $newName the replacement namespace-level constant name
     */
    private function fullyQualifiedReplacementName(string $oldName, string $newName): string
    {
        if (str_contains($newName, '\\')) {
            return ltrim($newName, '\\');
        }

        $namespace = $this->namespaceName($oldName);

        if ('' === $namespace) {
            return $newName;
        }

        return $namespace.'\\'.$newName;
    }

    /**
     * Returns the namespace part of one fully-qualified constant name.
     *
     * @param string $name the fully-qualified constant name
     */
    private function namespaceName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));
        array_pop($parts);

        return implode('\\', $parts);
    }

    /**
     * Returns the short name for one constant name.
     *
     * @param string $name the constant name
     */
    private function shortName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));

        return (string) end($parts);
    }
}
