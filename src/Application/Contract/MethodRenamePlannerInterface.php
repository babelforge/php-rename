<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Domain\Rename\MethodRenameRequest;
use PhpNoobs\PhpRename\Domain\Rename\RenamePlan;

/**
 * Plans method rename operations from a semantic member graph build.
 */
interface MethodRenamePlannerInterface
{
    /**
     * Plans a method rename.
     *
     * @param MethodRenameRequest        $request the method rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(MethodRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
