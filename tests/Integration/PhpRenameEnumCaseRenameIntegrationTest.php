<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Integration;

use BabelForge\PhpRename\Application\PhpRename;
use BabelForge\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers enum-case rename planning and AST application against real member-graph builds.
 */
final class PhpRenameEnumCaseRenameIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-enum-case-rename-'.str_replace('.', '', uniqid('', true));
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
     * Ensures enum-case renaming mutates locator-provided declaration, usage, and supported docblock references.
     */
    public function testItRenamesEnumCaseDeclarationUsageAndSupportedDocblockReferences(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeStatusFile($srcDirectory.'/Status.php');
        $this->writeRunnerFile($srcDirectory.'/Runner.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameEnumCase('App\\Status', 'ACTIVE', 'ENABLED');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(2, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertSame(2, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('case ENABLED;', $printedCode);
        self::assertStringContainsString('Status::ENABLED', $printedCode);
        self::assertStringContainsString('@see self::ENABLED', $printedCode);
        self::assertStringContainsString('Mentions ACTIVE in prose without changing free text.', $printedCode);
        self::assertStringNotContainsString('case ACTIVE;', $printedCode);
        self::assertStringNotContainsString('Status::ACTIVE', $printedCode);
        self::assertStringNotContainsString('@see self::ACTIVE', $printedCode);
    }

    /**
     * Writes the status enum fixture.
     *
     * @param string $filePath the file path
     */
    private function writeStatusFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            enum Status
            {
                /**
                 * Mentions ACTIVE in prose without changing free text.
                 *
                 * @see self::ACTIVE
                 */
                case ACTIVE;
            }
            PHP);
    }

    /**
     * Writes the runner fixture.
     *
     * @param string $filePath the file path
     */
    private function writeRunnerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Runner
            {
                public function run(): Status
                {
                    return Status::ACTIVE;
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
