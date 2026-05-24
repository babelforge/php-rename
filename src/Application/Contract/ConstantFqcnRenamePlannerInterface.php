<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Application\Contract;

use BabelForge\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use BabelForge\PhpRename\Domain\Rename\Plan\RenamePlan;
use BabelForge\PhpRename\Domain\Rename\Request\ConstantFqcnRenameRequest;

/**
 * Plans namespace-level constant FQCN renames.
 */
interface ConstantFqcnRenamePlannerInterface
{
    /**
     * Plans one namespace-level constant FQCN rename.
     *
     * @param ConstantFqcnRenameRequest  $request the constant FQCN rename request
     * @param MemberDependencyGraphBuild $build   the member graph build
     */
    public function plan(ConstantFqcnRenameRequest $request, MemberDependencyGraphBuild $build): RenamePlan;
}
