<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\NestedCallableLocalVariableRenameRequest;

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
