<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Integration;

use PhpNoobs\MemberGraph\Application\Source\Node\MemberGraphSourceNodeLocator;
use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenameResult;
use PhpNoobs\PhpRename\Domain\Rename\Transaction\RenameTransactionResult;
use PhpNoobs\PhpRename\Domain\Rename\Transaction\RenameTransactionStatus;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers transaction rebuild, commit, and rollback behavior against real member-graph builds.
 */
final class PhpRenameTransactionIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-transaction-'.str_replace('.', '', uniqid('', true));
        mkdir($this->workspace, 0o777, true);
    }

    /**
     * Removes the temporary integration workspace.
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
    }

    /**
     * Ensures dependent transaction actions see the semantic state produced by earlier actions.
     */
    public function testItCommitsDependentRenamesAgainstFreshInMemoryBuilds(): void
    {
        $renamer = $this->renamerWithFixture('Mailer.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                }
            }
            PHP);

        $transaction = $renamer->beginTransaction();

        $transaction->renameClassFqcn('App\\Mailer', 'App\\Infrastructure\\Sender');
        $transaction->renameMethod('App\\Infrastructure\\Sender', 'send', 'deliver');

        $result = $transaction->commit();
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertSame(RenameTransactionStatus::COMMITTED, $result->status);
        self::assertCount(2, $result->actionResults);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('namespace App\\Infrastructure;', $printedCode);
        self::assertStringContainsString('final class Sender', $printedCode);
        self::assertStringContainsString('public function deliver(): void', $printedCode);
        self::assertStringNotContainsString('final class Mailer', $printedCode);
        self::assertStringNotContainsString('public function send(): void', $printedCode);
        self::assertGreaterThan(0, count(MemberGraphSourceNodeLocator::fromBuild($result->finalBuild)->method('App\\Infrastructure\\Sender', 'deliver')));
    }

    /**
     * Ensures rollback restores files mutated before a later transaction action failed.
     */
    public function testItRollsBackTouchedVirtualFilesAfterFailure(): void
    {
        $renamer = $this->renamerWithFixture('Mailer.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                }

                public function deliver(): void
                {
                }
            }
            PHP);

        $transaction = $renamer->beginTransaction();

        $transaction->renameClassFqcn('App\\Mailer', 'App\\Infrastructure\\Sender');
        $failedResult = $transaction->renameMethod('App\\Infrastructure\\Sender', 'send', 'deliver');
        $failedStatus = $transaction->status();
        $rollbackResult = $transaction->rollback();
        $printedCode = $this->printedCode($rollbackResult->virtualFiles);

        self::assertSame(RenameTransactionStatus::FAILED, $failedStatus);
        self::assertSame(RenameDiagnosticSeverity::ERROR, $this->firstPlanDiagnosticSeverity($failedResult));
        self::assertSame(RenameTransactionStatus::ROLLED_BACK, $rollbackResult->status);
        self::assertStringContainsString('namespace App;', $printedCode);
        self::assertStringContainsString('final class Mailer', $printedCode);
        self::assertStringContainsString('public function send(): void', $printedCode);
        self::assertStringContainsString('public function deliver(): void', $printedCode);
        self::assertStringNotContainsString('namespace App\\Infrastructure;', $printedCode);
        self::assertStringNotContainsString('final class Sender', $printedCode);
    }

    /**
     * Ensures report-only conflicts keep the transaction committable while preserving diagnostics.
     */
    public function testItCommitsReportOnlyConflicts(): void
    {
        $renamer = $this->renamerWithFixture('Mailer.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                }

                public function deliver(): void
                {
                }
            }
            PHP);

        $transaction = $renamer->beginTransaction();

        $transaction->renameMethod('App\\Mailer', 'send', 'deliver', RenameConflictPolicy::REPORT);

        $result = $transaction->commit();
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertSame(RenameTransactionStatus::COMMITTED, $result->status);
        self::assertSame(RenameDiagnosticSeverity::WARNING, $this->firstTransactionDiagnosticSeverity($result));
        self::assertStringContainsString('public function deliver(): void', $printedCode);
    }

    /**
     * Ensures commit-and-save writes committed AST mutations to disk.
     */
    public function testItCommitsAndSavesUpdatedSourceFiles(): void
    {
        $renamer = $this->renamerWithFixture('Mailer.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                }
            }
            PHP);
        $sourceFilePath = $this->sourceFilePath('Mailer.php');

        $transaction = $renamer->beginTransaction();

        $transaction->renameClass('App\\Mailer', 'Sender');

        $result = $transaction->commitAndSave();
        $fileContents = (string) file_get_contents($sourceFilePath);

        self::assertSame(RenameTransactionStatus::COMMITTED, $result->status);
        self::assertStringContainsString('final class Sender', $fileContents);
        self::assertStringNotContainsString('final class Mailer', $fileContents);
    }

    /**
     * Ensures targeted commit-and-save writes only the requested physical source file.
     */
    public function testItCommitsAndSavesOneUpdatedSourceFile(): void
    {
        $renamer = $this->renamerWithFixtures([
            'Mailer.php' => <<<'PHP'
                <?php

                namespace App;

                final class Mailer
                {
                }
                PHP,
            'Notifier.php' => <<<'PHP'
                <?php

                namespace App;

                final class Notifier
                {
                }
                PHP,
        ]);
        $mailerFilePath = $this->sourceFilePath('Mailer.php');
        $notifierFilePath = $this->sourceFilePath('Notifier.php');

        $transaction = $renamer->beginTransaction();

        $transaction->renameClass('App\\Mailer', 'Sender');
        $transaction->renameClass('App\\Notifier', 'Alerter');

        $result = $transaction->commitAndSaveSourceFile($mailerFilePath);
        $mailerContents = (string) file_get_contents($mailerFilePath);
        $notifierContents = (string) file_get_contents($notifierFilePath);

        self::assertSame(RenameTransactionStatus::COMMITTED, $result->status);
        self::assertStringContainsString('final class Sender', $mailerContents);
        self::assertStringNotContainsString('final class Mailer', $mailerContents);
        self::assertStringContainsString('final class Notifier', $notifierContents);
        self::assertStringNotContainsString('final class Alerter', $notifierContents);
    }

    /**
     * Creates a renamer from one source fixture.
     *
     * @param string $fileName the source file name
     * @param string $contents the source file contents
     */
    private function renamerWithFixture(string $fileName, string $contents): PhpRename
    {
        return $this->renamerWithFixtures([$fileName => $contents]);
    }

    /**
     * Creates a renamer from source fixtures.
     *
     * @param array<string, string> $files the source file names mapped to contents
     */
    private function renamerWithFixtures(array $files): PhpRename
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);

        foreach ($files as $fileName => $contents) {
            file_put_contents($srcDirectory.'/'.$fileName, $contents);
        }

        return PhpRename::fromDirectory([$srcDirectory], $cacheFilePath);
    }

    /**
     * Returns the absolute path to one fixture source file.
     *
     * @param string $fileName the source file name
     */
    private function sourceFilePath(string $fileName): string
    {
        $filePath = realpath($this->workspace.'/src/'.$fileName);

        if (false === $filePath) {
            throw new \RuntimeException("Source file $fileName not found");
        }

        return $filePath;
    }

    /**
     * Returns the first plan diagnostic severity from one rename result.
     *
     * @param RenameResult $result the rename result
     */
    private function firstPlanDiagnosticSeverity(RenameResult $result): ?RenameDiagnosticSeverity
    {
        foreach ($result->plan->diagnostics as $diagnostic) {
            return $diagnostic->severity;
        }

        return null;
    }

    /**
     * Returns the first transaction diagnostic severity.
     *
     * @param RenameTransactionResult $result the transaction result
     */
    private function firstTransactionDiagnosticSeverity(RenameTransactionResult $result): ?RenameDiagnosticSeverity
    {
        foreach ($result->diagnostics as $diagnostic) {
            return $diagnostic->severity;
        }

        return null;
    }

    /**
     * Prints all virtual files into one assertion string.
     *
     * @param iterable<VirtualPhpSourceFile> $virtualFiles the virtual files to print
     */
    private function printedCode(iterable $virtualFiles): string
    {
        $printedCode = '';

        foreach ($virtualFiles as $virtualFile) {
            $printedCode .= "\n".$virtualFile->standardPrint($virtualFile->nodes);
        }

        return $printedCode;
    }

    /**
     * Removes one directory recursively.
     *
     * @param string $directory the directory to remove
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            if ($file->isDir()) {
                rmdir($file->getPathname());
                continue;
            }

            unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
