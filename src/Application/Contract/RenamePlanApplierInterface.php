<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Domain\Rename\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\RenameResult;

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
