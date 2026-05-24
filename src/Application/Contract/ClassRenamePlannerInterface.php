<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\ClassRenameRequest;

/**
 * Plans class-like owner rename operations from a semantic member graph build.
 */
interface ClassRenamePlannerInterface
{
    /**
     * Plans a class-like owner rename.
     *
     * @param ClassRenameRequest         $request the class rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(ClassRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
