<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\MethodRenameRequest;

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
