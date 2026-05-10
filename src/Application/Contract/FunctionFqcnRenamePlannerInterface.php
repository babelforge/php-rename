<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionFqcnRenameRequest;

/**
 * Plans fully-qualified function rename operations from a semantic member graph build.
 */
interface FunctionFqcnRenamePlannerInterface
{
    /**
     * Plans a fully-qualified function rename.
     *
     * @param FunctionFqcnRenameRequest  $request the function FQCN rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(FunctionFqcnRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
