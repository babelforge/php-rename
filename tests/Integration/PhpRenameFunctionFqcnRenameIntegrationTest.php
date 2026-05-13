<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Integration;

use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers function FQCN rename planning and AST application against real member-graph builds.
 */
final class PhpRenameFunctionFqcnRenameIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-function-fqcn-rename-'.str_replace('.', '', uniqid('', true));
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
     * Ensures function FQCN renaming updates declaration namespace, function imports, and same-namespace consumers.
     */
    public function testItRenamesFunctionFqcnAndUpdatesFunctionImports(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/Controller', 0o777, true);
        mkdir($srcDirectory.'/Admin', 0o777, true);

        $this->writeFunctionsFile($srcDirectory.'/App/functions.php');
        $this->writeSameNamespaceConsumerFile($srcDirectory.'/App/SameNamespaceConsumer.php');
        $this->writeImportedConsumerFile($srcDirectory.'/Controller/ImportedConsumer.php');
        $this->writeSecondImportedConsumerFile($srcDirectory.'/Admin/SecondImportedConsumer.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameFunctionFqcn('App\\send_mail', 'Tools\\deliver_mail');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertGreaterThanOrEqual(4, count($result->plan->operations));
        self::assertCount(0, $result->diagnostics);
        self::assertSame(4, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('namespace Tools;', $printedCode);
        self::assertStringContainsString('function deliver_mail(', $printedCode);
        self::assertStringContainsString('use function Tools\\deliver_mail;', $printedCode);
        self::assertStringContainsString('return deliver_mail();', $printedCode);
        self::assertStringContainsString('@see Tools\\deliver_mail()', $printedCode);
        self::assertStringNotContainsString('function send_mail(', $printedCode);
        self::assertStringNotContainsString('use function App\\send_mail;', $printedCode);
        self::assertStringNotContainsString('@see App\\send_mail()', $printedCode);
    }

    /**
     * Ensures function FQCN renaming preserves explicit aliases from grouped imports.
     */
    public function testItPreservesExplicitAliasWhenFunctionFqcnRenameUpdatesGroupedImport(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/Controller', 0o777, true);

        $this->writeFunctionsFile($srcDirectory.'/App/functions.php');
        file_put_contents($srcDirectory.'/Controller/Consumer.php', <<<'PHP'
            <?php

            namespace Controller;

            use function App\{send_mail as legacy_send_mail};

            final class Consumer
            {
                public function run(): string
                {
                    return legacy_send_mail();
                }
            }
            PHP);

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameFunctionFqcn('App\\send_mail', 'Tools\\deliver_mail');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('use function Tools\\deliver_mail as legacy_send_mail;', $printedCode);
        self::assertStringContainsString('return legacy_send_mail();', $printedCode);
        self::assertStringNotContainsString('use function App\\{Tools\\deliver_mail', $printedCode);
    }

    /**
     * Writes the function fixture targeted by the FQCN rename.
     *
     * @param string $filePath the file path
     */
    private function writeFunctionsFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            /**
             * @see App\send_mail()
             */
            function send_mail(): string
            {
                return 'sent';
            }
            PHP);
    }

    /**
     * Writes a consumer fixture that calls the function from the same namespace without an import.
     *
     * @param string $filePath the file path
     */
    private function writeSameNamespaceConsumerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class SameNamespaceConsumer
            {
                public function run(): string
                {
                    return send_mail();
                }
            }
            PHP);
    }

    /**
     * Writes a consumer fixture with an existing function import.
     *
     * @param string $filePath the file path
     */
    private function writeImportedConsumerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace Controller;

            use function App\send_mail;

            final class ImportedConsumer
            {
                public function run(): string
                {
                    return send_mail();
                }
            }
            PHP);
    }

    /**
     * Writes a second consumer fixture with an existing function import.
     *
     * @param string $filePath the file path
     */
    private function writeSecondImportedConsumerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace Admin;

            use function App\send_mail;

            final class SecondImportedConsumer
            {
                public function run(): string
                {
                    return send_mail();
                }
            }
            PHP);
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
     * Counts virtual files marked as updated.
     *
     * @param iterable<VirtualPhpSourceFile> $virtualFiles the virtual files to inspect
     */
    private function updatedVirtualFileCount(iterable $virtualFiles): int
    {
        $count = 0;

        foreach ($virtualFiles as $virtualFile) {
            if ($virtualFile->isUpdated()) {
                ++$count;
            }
        }

        return $count;
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
