<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\PhpParser\Application;

use PhpNoobs\PhpRename\Domain\Rename\RenameOperation;

/**
 * Applies AST node mutations for rename operations.
 */
interface RenameNodeApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool;

    /**
     * Applies one AST node rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): bool;
}
