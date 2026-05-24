<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\ClassConstantRenameRequest;

/**
 * Plans class-constant rename operations from a semantic member graph build.
 */
interface ClassConstantRenamePlannerInterface
{
    /**
     * Plans a class-constant rename.
     *
     * @param ClassConstantRenameRequest $request the class-constant rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(ClassConstantRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
