<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\FunctionFqcnRenameRequest;

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
