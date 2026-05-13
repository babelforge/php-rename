<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Application\Contract;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenamePlan;
use PhpNoobs\PhpRename\Domain\Rename\Request\NestedCallableRenameRequest;

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
