<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Infrastructure\PhpParser\Docblock;

use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperation;
use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperationRole;
use BabelForge\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use BabelForge\PhpRename\Infrastructure\PhpParser\Application\RenameApplicationContext;
use BabelForge\PhpRename\Infrastructure\PhpParser\Application\RenameMetadataApplierInterface;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\Function_;

/**
 * Renames supported function references inside matched function docblocks.
 */
final readonly class FunctionDocblockRenameApplier implements RenameMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool
    {
        return RenameSymbolKind::FUNCTION_ === $operation->symbolKind
            && RenameOperationRole::DECLARATION === $operation->role
            && $operation->node instanceof Function_;
    }

    /**
     * Applies function docblock reference changes for one rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): void
    {
        if (!$operation->node instanceof Function_) {
            return;
        }

        $docComment = $operation->node->getDocComment();

        if (null === $docComment) {
            return;
        }

        $updatedText = $this->renameSupportedFunctionReferences(
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
     * Renames supported function references inside one docblock text.
     *
     * @param string $text    the docblock text
     * @param string $oldName the current function short name
     * @param string $newName the replacement function short name
     */
    private function renameSupportedFunctionReferences(string $text, string $oldName, string $newName): string
    {
        $updatedText = $this->renameSupportedFunctionName(
            text: $text,
            oldName: ltrim($oldName, '\\'),
            newName: $this->fullyQualifiedReplacementName($oldName, $newName),
        );

        if (!str_contains($oldName, '\\')) {
            return $updatedText;
        }

        return $this->renameSupportedFunctionName(
            text: $updatedText,
            oldName: $this->shortName($oldName),
            newName: $this->shortName($newName),
        );
    }

    /**
     * Renames one supported function name form inside one docblock text.
     *
     * @param string $text    the docblock text
     * @param string $oldName the current function name form
     * @param string $newName the replacement function name form
     */
    private function renameSupportedFunctionName(string $text, string $oldName, string $newName): string
    {
        $quotedOldName = preg_quote(ltrim($oldName, '\\'), '/');

        return preg_replace(
            pattern: '/(@see\s+[^\r\n]*)\b'.$quotedOldName.'(?=\s*\()/',
            replacement: '$1'.ltrim($newName, '\\'),
            subject: $text,
        ) ?? $text;
    }

    /**
     * Returns the fully-qualified replacement name for one function rename.
     *
     * @param string $oldName the current function name
     * @param string $newName the replacement function name
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
     * Returns the namespace part of one fully-qualified function name.
     *
     * @param string $name the fully-qualified function name
     */
    private function namespaceName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));
        array_pop($parts);

        return implode('\\', $parts);
    }

    /**
     * Returns the short name for one function name.
     *
     * @param string $name the function name
     */
    private function shortName(string $name): string
    {
        $parts = explode('\\', ltrim($name, '\\'));

        return (string) end($parts);
    }
}
