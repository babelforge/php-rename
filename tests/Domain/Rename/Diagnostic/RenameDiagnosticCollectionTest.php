<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Domain\Rename\Diagnostic;

use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnostic;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
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
