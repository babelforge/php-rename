<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Infrastructure\MemberGraph\Planner;

use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpParser\Node;

/**
 * Carries a nested callable search container and its source file.
 */
final readonly class NestedCallableContainer
{
    /**
     * Constructor.
     *
     * @param VirtualPhpSourceFile $file the virtual file containing the container
     * @param Node|list<Node>      $node the container node or root node list
     */
    public function __construct(
        public VirtualPhpSourceFile $file,
        public Node|array $node,
    ) {
    }
}
