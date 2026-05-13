<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\NestedCallableLocalVariableRenameRequest;

/**
 * Plans nested callable local variable renames.
 */
interface NestedCallableLocalVariableRenamePlannerInterface
{
    /**
     * Plans one nested callable local variable rename.
     *
     * @param NestedCallableLocalVariableRenameRequest $request the nested callable local variable rename request
     * @param MemberDependencyGraphBuild               $build   the member graph build
     */
    public function planLocalVariable(NestedCallableLocalVariableRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
