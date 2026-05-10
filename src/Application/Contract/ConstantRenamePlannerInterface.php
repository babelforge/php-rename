<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\ConstantRenameRequest;

/**
 * Plans namespace-level constant renames.
 */
interface ConstantRenamePlannerInterface
{
    /**
     * Plans one namespace-level constant rename.
     *
     * @param ConstantRenameRequest      $request the constant rename request
     * @param MemberDependencyGraphBuild $build   the member graph build
     */
    public function plan(ConstantRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
