<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Integration;

use BabelForge\PhpRename\Application\PhpRename;
use BabelForge\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers namespace-level constant rename planning and AST application against real member-graph builds.
 */
final class PhpRenameConstantRenameIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-constant-rename-'.str_replace('.', '', uniqid('', true));
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
     * Ensures constant renaming mutates locator-provided declarations, usages, and constant imports.
     */
    public function testItRenamesConstantDeclarationUsageAndImports(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App/Config', 0o777, true);
        mkdir($srcDirectory.'/Controller', 0o777, true);

        $this->writeConstantsFile($srcDirectory.'/App/Config/constants.php');
        $this->writeSameNamespaceConsumerFile($srcDirectory.'/App/Config/SameNamespaceConsumer.php');
        $this->writeImportedConsumerFile($srcDirectory.'/Controller/Consumer.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameConstant('App\\Config\\ENABLED', 'ACTIVE');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertGreaterThanOrEqual(3, count($result->plan->operations));
        self::assertCount(0, $result->diagnostics);
        self::assertSame(3, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('const ACTIVE = true;', $printedCode);
        self::assertStringContainsString('@see ACTIVE', $printedCode);
        self::assertStringContainsString('@see App\\Config\\ACTIVE', $printedCode);
        self::assertStringContainsString('use const App\\Config\\ACTIVE;', $printedCode);
        self::assertStringContainsString('return ACTIVE;', $printedCode);
        self::assertStringNotContainsString('const ENABLED = true;', $printedCode);
        self::assertStringNotContainsString('@see ENABLED', $printedCode);
        self::assertStringNotContainsString('@see App\\Config\\ENABLED', $printedCode);
        self::assertStringNotContainsString('use const App\\Config\\ENABLED;', $printedCode);
    }

    /**
     * Writes the constant fixture targeted by the rename.
     *
     * @param string $filePath the file path
     */
    private function writeConstantsFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App\Config;

            /**
             * @see ENABLED
             * @see App\Config\ENABLED
             */
            const ENABLED = true;
            PHP);
    }

    /**
     * Writes a consumer fixture that fetches the constant from the same namespace without an import.
     *
     * @param string $filePath the file path
     */
    private function writeSameNamespaceConsumerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App\Config;

            final class SameNamespaceConsumer
            {
                public function enabled(): bool
                {
                    return ENABLED;
                }
            }
            PHP);
    }

    /**
     * Writes a consumer fixture with an existing constant import.
     *
     * @param string $filePath the file path
     */
    private function writeImportedConsumerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace Controller;

            use const App\Config\ENABLED;

            final class Consumer
            {
                public function enabled(): bool
                {
                    return ENABLED;
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
