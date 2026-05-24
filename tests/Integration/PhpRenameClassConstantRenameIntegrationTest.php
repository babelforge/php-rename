<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Integration;

use BabelForge\PhpRename\Application\PhpRename;
use BabelForge\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers class-constant rename planning and AST application against real member-graph builds.
 */
final class PhpRenameClassConstantRenameIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-class-constant-rename-'.str_replace('.', '', uniqid('', true));
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
     * Ensures class-constant renaming mutates locator-provided declaration, usage, and supported docblock references.
     */
    public function testItRenamesClassConstantDeclarationUsageAndSupportedDocblockReferences(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');
        $this->writeRunnerFile($srcDirectory.'/Runner.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameClassConstant('App\\Mailer', 'DEFAULT_TRANSPORT', 'FALLBACK_TRANSPORT');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(2, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertSame(2, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('public const FALLBACK_TRANSPORT', $printedCode);
        self::assertStringContainsString('Mailer::FALLBACK_TRANSPORT', $printedCode);
        self::assertStringContainsString('@see self::FALLBACK_TRANSPORT', $printedCode);
        self::assertStringContainsString('Mentions DEFAULT_TRANSPORT in prose without changing free text.', $printedCode);
        self::assertStringNotContainsString('public const DEFAULT_TRANSPORT', $printedCode);
        self::assertStringNotContainsString('Mailer::DEFAULT_TRANSPORT', $printedCode);
        self::assertStringNotContainsString('@see self::DEFAULT_TRANSPORT', $printedCode);
    }

    /**
     * Ensures class-constant renaming mutates only the targeted item in grouped declarations.
     */
    public function testItRenamesOneClassConstantInsideGroupedDeclaration(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeGroupedConstantsMailerFile($srcDirectory.'/Mailer.php');
        $this->writeGroupedConstantsRunnerFile($srcDirectory.'/Runner.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameClassConstant('App\\Mailer', 'LEGACY_TRANSPORT', 'FALLBACK_TRANSPORT');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(2, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertSame(2, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('public const DEFAULT_TRANSPORT = \'smtp\', FALLBACK_TRANSPORT = \'sendmail\';', $printedCode);
        self::assertStringContainsString('Mailer::DEFAULT_TRANSPORT', $printedCode);
        self::assertStringContainsString('Mailer::FALLBACK_TRANSPORT', $printedCode);
        self::assertStringContainsString('@see self::FALLBACK_TRANSPORT', $printedCode);
        self::assertStringNotContainsString('LEGACY_TRANSPORT', $printedCode);
    }

    /**
     * Writes the mailer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                /**
                 * Mentions DEFAULT_TRANSPORT in prose without changing free text.
                 *
                 * @see self::DEFAULT_TRANSPORT
                 */
                public const DEFAULT_TRANSPORT = 'smtp';
            }
            PHP);
    }

    /**
     * Writes a fixture with a grouped class-constant declaration.
     *
     * @param string $filePath the file path
     */
    private function writeGroupedConstantsMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                /**
                 * @see self::LEGACY_TRANSPORT
                 */
                public const DEFAULT_TRANSPORT = 'smtp', LEGACY_TRANSPORT = 'sendmail';
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
                public function run(): string
                {
                    return Mailer::DEFAULT_TRANSPORT;
                }
            }
            PHP);
    }

    /**
     * Writes a consumer fixture for grouped class constants.
     *
     * @param string $filePath the file path
     */
    private function writeGroupedConstantsRunnerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Runner
            {
                public function run(): string
                {
                    return Mailer::DEFAULT_TRANSPORT.' '.Mailer::LEGACY_TRANSPORT;
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
