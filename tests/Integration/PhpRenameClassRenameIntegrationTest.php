<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Integration;

use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers class-like owner rename planning and AST application against real member-graph builds.
 */
final class PhpRenameClassRenameIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-class-rename-'.str_replace('.', '', uniqid('', true));
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
     * Ensures class-like owner renaming mutates locator-provided declaration, usages, and supported docblock references.
     */
    public function testItRenamesClassDeclarationUsagesAndSupportedDocblockReferences(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');
        $this->writeSpecialMailerFile($srcDirectory.'/SpecialMailer.php');
        $this->writeRunnerFile($srcDirectory.'/Runner.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameClass('App\\Mailer', 'TransactionalMailer');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertGreaterThanOrEqual(4, count($result->plan->operations));
        self::assertCount(0, $result->diagnostics);
        self::assertSame(3, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('class TransactionalMailer', $printedCode);
        self::assertStringContainsString('extends \\App\\TransactionalMailer', $printedCode);
        self::assertStringContainsString('new \\App\\TransactionalMailer()', $printedCode);
        self::assertStringContainsString('\\App\\TransactionalMailer::class', $printedCode);
        self::assertStringContainsString('@see TransactionalMailer', $printedCode);
        self::assertStringContainsString('@mixin TransactionalMailer', $printedCode);
        self::assertStringContainsString('@template T of TransactionalMailer', $printedCode);
        self::assertStringContainsString('@return array<TransactionalMailer>', $printedCode);
        self::assertStringContainsString('Mentions Mailer in prose without changing free text.', $printedCode);
        self::assertStringNotContainsString('class Mailer', $printedCode);
        self::assertStringNotContainsString('extends Mailer', $printedCode);
        self::assertStringNotContainsString('new Mailer()', $printedCode);
        self::assertStringNotContainsString('\\App\\Mailer::class', $printedCode);
        self::assertStringNotContainsString('@see Mailer', $printedCode);
        self::assertStringNotContainsString('@mixin Mailer', $printedCode);
        self::assertStringNotContainsString('@template T of Mailer', $printedCode);
        self::assertStringNotContainsString('@return array<Mailer>', $printedCode);
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

            /**
             * Mentions Mailer in prose without changing free text.
             *
             * @see Mailer
             * @mixin Mailer
             * @template T of Mailer
             * @return array<Mailer>
             */
            class Mailer
            {
            }
            PHP);
    }

    /**
     * Writes the special mailer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeSpecialMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class SpecialMailer extends Mailer
            {
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
                    $mailer = new Mailer();

                    return Mailer::class;
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
