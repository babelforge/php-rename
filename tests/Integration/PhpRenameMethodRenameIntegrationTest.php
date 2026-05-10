<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Integration;

use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers method rename planning and AST application against real member-graph builds.
 */
final class PhpRenameMethodRenameIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-method-rename-'.str_replace('.', '', uniqid('', true));
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
     * Ensures method renaming mutates only locator-provided declaration and usage nodes.
     */
    public function testItRenamesMethodDeclarationAndResolvedUsageNodes(): void
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

        $result = $renamer->renameMethod('App\\Mailer', 'send', 'deliver');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(2, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertSame(2, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('function deliver(', $printedCode);
        self::assertStringContainsString('$mailer->deliver()', $printedCode);
        self::assertStringNotContainsString('function send(', $printedCode);
        self::assertStringNotContainsString('$mailer->send()', $printedCode);
    }

    /**
     * Ensures method renaming updates supported docblock references on matched declaration nodes.
     */
    public function testItRenamesSupportedMethodDocblockReferences(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeDocumentedMailerFile($srcDirectory.'/Mailer.php');
        $this->writeRunnerFile($srcDirectory.'/Runner.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameMethod('App\\Mailer', 'send', 'deliver');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(2, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('@see self::deliver()', $printedCode);
        self::assertStringContainsString('@method void deliver()', $printedCode);
        self::assertStringNotContainsString('@see self::send()', $printedCode);
        self::assertStringNotContainsString('@method void send()', $printedCode);
        self::assertStringContainsString('Calls send in prose without changing free text.', $printedCode);
    }

    /**
     * Ensures method renaming updates trait alias adaptation source references and projected consumer calls.
     */
    public function testItRenamesTraitAliasAdaptationSourcesAndProjectedConsumerCalls(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeTraitAliasFixture($srcDirectory.'/TraitAliasFixture.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameMethod('App\\MailerTrait', 'send', 'deliver');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(3, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertSame(3, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('function deliver(', $printedCode);
        self::assertStringContainsString('deliver as traitSend', $printedCode);
        self::assertStringContainsString('$mailer->deliver()', $printedCode);
        self::assertStringContainsString('$mailer->traitSend()', $printedCode);
        self::assertStringNotContainsString('function send(', $printedCode);
        self::assertStringNotContainsString('send as traitSend', $printedCode);
        self::assertStringNotContainsString('$mailer->send()', $printedCode);
    }

    /**
     * Ensures method renaming updates trait precedence adaptation references.
     */
    public function testItRenamesTraitPrecedenceAdaptationReferences(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeTraitPrecedenceFixture($srcDirectory.'/TraitPrecedenceFixture.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameMethod('App\\PrimaryMailerTrait', 'send', 'deliver');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(3, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertSame(3, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('function deliver(', $printedCode);
        self::assertStringContainsString('\\App\\PrimaryMailerTrait::deliver insteadof \\App\\SecondaryMailerTrait', $printedCode);
        self::assertStringContainsString('$mailer->deliver()', $printedCode);
        self::assertStringNotContainsString('\\App\\PrimaryMailerTrait::send insteadof \\App\\SecondaryMailerTrait', $printedCode);
        self::assertStringNotContainsString('$mailer->send()', $printedCode);
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
                public function send(): void
                {
                }
            }
            PHP);
    }

    /**
     * Writes the documented mailer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeDocumentedMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            /**
             * @method void send()
             */
            final class Mailer
            {
                /**
                 * Calls send in prose without changing free text.
                 *
                 * @see self::send()
                 */
                public function send(): void
                {
                }
            }
            PHP);
    }

    /**
     * Writes the trait alias fixture.
     *
     * @param string $filePath the file path
     */
    private function writeTraitAliasFixture(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            trait MailerTrait
            {
                public function send(): void
                {
                }
            }

            final class Mailer
            {
                use MailerTrait {
                    send as traitSend;
                }
            }

            final class Runner
            {
                public function run(Mailer $mailer): void
                {
                    $mailer->send();
                    $mailer->traitSend();
                }
            }
            PHP);
    }

    /**
     * Writes the trait precedence fixture.
     *
     * @param string $filePath the file path
     */
    private function writeTraitPrecedenceFixture(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            trait PrimaryMailerTrait
            {
                public function send(): void
                {
                }
            }

            trait SecondaryMailerTrait
            {
                public function send(): void
                {
                }
            }

            final class Mailer
            {
                use PrimaryMailerTrait;
                use SecondaryMailerTrait {
                    PrimaryMailerTrait::send insteadof SecondaryMailerTrait;
                }
            }

            final class Runner
            {
                public function run(Mailer $mailer): void
                {
                    $mailer->send();
                }
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
                public function run(Mailer $mailer): void
                {
                    $mailer->send();
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
