<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Domain\Rename\Operation;

use BabelForge\PhpRename\Domain\Rename\Symbol\RenameSymbolKind;
use BabelForge\PhpSource\VirtualPhpSourceFile;
use PhpParser\Node;

/**
 * Describes a single AST rename operation.
 */
final readonly class RenameOperation
{
    /**
     * Constructor.
     *
     * @param RenameSymbolKind     $symbolKind the renamed symbol kind
     * @param RenameOperationRole  $role       the operation role in the rename plan
     * @param VirtualPhpSourceFile $file       the virtual source file containing the node
     * @param Node                 $node       the AST node to mutate
     * @param string               $oldName    the current symbol name
     * @param string               $newName    the replacement symbol name
     */
    public function __construct(
        public RenameSymbolKind $symbolKind,
        public RenameOperationRole $role,
        public VirtualPhpSourceFile $file,
        public Node $node,
        public string $oldName,
        public string $newName,
    ) {
    }
}
