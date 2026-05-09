<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Domain\Rename\Diagnostic;

use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
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
