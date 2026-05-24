<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\EnumCaseRenameRequest;

/**
 * Plans enum-case rename operations from a semantic member graph build.
 */
interface EnumCaseRenamePlannerInterface
{
    /**
     * Plans an enum-case rename.
     *
     * @param EnumCaseRenameRequest      $request the enum-case rename request
     * @param MemberDependencyGraphBuild $build   the member graph build used to resolve declarations and usages
     */
    public function plan(EnumCaseRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
