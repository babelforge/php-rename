<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\ParameterRenameRequest;

/**
 * Plans parameter rename operations from a semantic member graph build.
 */
interface ParameterRenamePlannerInterface
{
    /**
     * Plans a parameter rename.
     *
     * @param ParameterRenameRequest     $request the parameter rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(ParameterRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
