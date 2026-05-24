<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\NestedCallableRenameRequest;

/**
 * Plans nested callable parameter renames.
 */
interface NestedCallableRenamePlannerInterface
{
    /**
     * Plans a nested callable parameter rename.
     *
     * @param NestedCallableRenameRequest $request the nested callable rename request
     * @param MemberDependencyGraphBuild  $build   the member graph build
     */
    public function plan(NestedCallableRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
