<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\ClassFqcnRenameRequest;

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
