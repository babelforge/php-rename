<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Diagnostic;

/**
 * Collection of rename diagnostics.
 *
 * @implements \IteratorAggregate<RenameDiagnostic>
 */
final class RenameDiagnosticCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var list<RenameDiagnostic>
     */
    private array $diagnostics = [];

    /**
     * Adds a diagnostic.
     *
     * @param RenameDiagnostic $diagnostic the diagnostic to add
     */
    public function add(RenameDiagnostic $diagnostic): self
    {
        $this->diagnostics[] = $diagnostic;

        return $this;
    }

    /**
     * Creates an empty diagnostic collection.
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Indicates whether the collection contains at least one error diagnostic.
     */
    public function hasErrors(): bool
    {
        foreach ($this->diagnostics as $diagnostic) {
            if (RenameDiagnosticSeverity::ERROR === $diagnostic->severity) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the collection iterator.
     *
     * @return \Traversable<RenameDiagnostic>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->diagnostics;
    }

    /**
     * Counts diagnostics.
     */
    public function count(): int
    {
        return count($this->diagnostics);
    }
}
