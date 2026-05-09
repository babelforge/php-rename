<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Domain\Rename;

use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\RenameDiagnosticSeverity;
use PHPUnit\Framework\TestCase;

/**
 * Tests rename diagnostic collection behavior.
 */
final class RenameDiagnosticCollectionTest extends TestCase
{
    /**
     * Ensures that diagnostics can be counted and iterated.
     */
    public function testItCountsAndIteratesDiagnostics(): void
    {
        $diagnostic = new RenameDiagnostic(
            severity: RenameDiagnosticSeverity::WARNING,
            message: 'A warning.',
        );

        $collection = RenameDiagnosticCollection::empty()->add($diagnostic);

        self::assertCount(1, $collection);
        self::assertSame([$diagnostic], iterator_to_array($collection));
    }
}
