<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename;

/**
 * Collection of AST rename operations.
 *
 * @implements \IteratorAggregate<RenameOperation>
 */
final class RenameOperationCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var list<RenameOperation>
     */
    private array $operations = [];

    /**
     * Adds an operation.
     *
     * @param RenameOperation $operation the operation to add
     */
    public function add(RenameOperation $operation): self
    {
        $this->operations[] = $operation;

        return $this;
    }

    /**
     * Creates an empty operation collection.
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Returns the collection iterator.
     *
     * @return \Traversable<RenameOperation>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->operations;
    }

    /**
     * Counts operations.
     */
    public function count(): int
    {
        return count($this->operations);
    }
}
