<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Infrastructure\PhpParser\Application;

use BabelForge\PhpRename\Domain\Rename\Operation\RenameOperation;

/**
 * Applies metadata mutations associated with a rename operation.
 */
interface RenameMetadataApplierInterface
{
    /**
     * Indicates whether this applier supports the rename operation.
     *
     * @param RenameOperation $operation the rename operation to inspect
     */
    public function supports(RenameOperation $operation): bool;

    /**
     * Applies metadata changes for one rename operation.
     *
     * @param RenameOperation          $operation the rename operation to apply
     * @param RenameApplicationContext $context   the rename application context
     */
    public function apply(RenameOperation $operation, RenameApplicationContext $context): void;
}
