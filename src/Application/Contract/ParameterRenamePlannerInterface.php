<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\ParameterRenameRequest;

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
