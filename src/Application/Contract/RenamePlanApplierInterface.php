<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Plan\RenameResult;

/**
 * Applies a rename plan to virtual file AST nodes.
 */
interface RenamePlanApplierInterface
{
    /**
     * Applies a rename plan.
     *
     * @param RenamePlan                 $plan  the rename plan to apply
     * @param MemberDependencyGraphBuild $build the member graph build containing virtual files
     */
    public function apply(RenamePlan $plan, MemberDependencyGraphBuild $build): RenameResult;
}
