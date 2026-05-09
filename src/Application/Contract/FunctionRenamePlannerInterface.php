<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionRenameRequest;

/**
 * Plans function rename operations from a semantic member graph build.
 */
interface FunctionRenamePlannerInterface
{
    /**
     * Plans a function rename.
     *
     * @param FunctionRenameRequest      $request the function rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(FunctionRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
