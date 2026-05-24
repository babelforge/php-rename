<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\ClassFqcnRenameRequest;

/**
 * Plans fully-qualified class-like owner rename operations from a semantic member graph build.
 */
interface ClassFqcnRenamePlannerInterface
{
    /**
     * Plans a fully-qualified class-like owner rename.
     *
     * @param ClassFqcnRenameRequest     $request the class FQCN rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(ClassFqcnRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
