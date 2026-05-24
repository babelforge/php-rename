<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Integration;

use BabelForge\PhpRename\Application\PhpRename;
use BabelForge\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers PHP attribute reference rename planning and AST application against real member-graph builds.
 */
final class PhpRenameAttributeReferenceIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-attribute-reference-'.str_replace('.', '', uniqid('', true));
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
     * Ensures class FQCN renaming mutates class-like references inside attribute arguments.
     */
    public function testItRenamesClassLikeReferencesInsideAttributes(): void
    {
        $renamer = $this->renamerWithFixture('classes.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
            }

            #[SomeAttribute(target: Mailer::class)]
            final class Consumer
            {
            }
            PHP);

        $result = $renamer->renameClassFqcn('App\\Mailer', 'Tools\\Sender');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('namespace Tools;', $printedCode);
        self::assertStringContainsString('final class Sender', $printedCode);
        self::assertStringContainsString('use Tools\\Sender;', $printedCode);
        self::assertStringContainsString('#[\\App\\SomeAttribute(target: Sender::class)]', $printedCode);
        self::assertStringNotContainsString('Mailer::class', $printedCode);
    }

    /**
     * Ensures class-constant renaming mutates class-constant fetches inside attribute arguments.
     */
    public function testItRenamesClassConstantReferencesInsideAttributes(): void
    {
        $renamer = $this->renamerWithFixture('classes.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public const DEFAULT_TRANSPORT = 'smtp';
            }

            #[SomeAttribute(transport: Mailer::DEFAULT_TRANSPORT)]
            final class Consumer
            {
            }
            PHP);

        $result = $renamer->renameClassConstant('App\\Mailer', 'DEFAULT_TRANSPORT', 'FALLBACK_TRANSPORT');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('public const FALLBACK_TRANSPORT = \'smtp\';', $printedCode);
        self::assertStringContainsString('#[\\App\\SomeAttribute(transport: \\App\\Mailer::FALLBACK_TRANSPORT)]', $printedCode);
        self::assertStringNotContainsString('DEFAULT_TRANSPORT', $printedCode);
    }

    /**
     * Ensures enum-case renaming mutates enum-case fetches inside attribute arguments.
     */
    public function testItRenamesEnumCaseReferencesInsideAttributes(): void
    {
        $renamer = $this->renamerWithFixture('classes.php', <<<'PHP'
            <?php

            namespace App;

            enum Status
            {
                case ACTIVE;
            }

            #[SomeAttribute(status: Status::ACTIVE)]
            final class Consumer
            {
            }
            PHP);

        $result = $renamer->renameEnumCase('App\\Status', 'ACTIVE', 'ENABLED');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('case ENABLED;', $printedCode);
        self::assertStringContainsString('#[\\App\\SomeAttribute(status: \\App\\Status::ENABLED)]', $printedCode);
        self::assertStringNotContainsString('Status::ACTIVE', $printedCode);
    }

    /**
     * Ensures namespace-level constant renaming mutates constant fetches inside attribute arguments.
     */
    public function testItRenamesNamespaceConstantReferencesInsideAttributes(): void
    {
        $renamer = $this->renamerWithFixture('classes.php', <<<'PHP'
            <?php

            namespace App\Config;

            const ENABLED = true;

            #[SomeAttribute(option: ENABLED)]
            final class Consumer
            {
            }
            PHP);

        $result = $renamer->renameConstant('App\\Config\\ENABLED', 'ACTIVE');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('const ACTIVE = true;', $printedCode);
        self::assertStringContainsString('#[\\App\\Config\\SomeAttribute(option: ACTIVE)]', $printedCode);
        self::assertStringNotContainsString('ENABLED', $printedCode);
    }

    /**
     * Ensures function renaming mutates first-class callable references inside attribute arguments.
     */
    public function testItRenamesFunctionReferencesInsideAttributes(): void
    {
        $renamer = $this->renamerWithFixture('functions.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(): string
            {
                return 'sent';
            }

            #[SomeAttribute(callback: send_mail(...))]
            final class Consumer
            {
            }
            PHP);

        $result = $renamer->renameFunction('App\\send_mail', 'deliver_mail');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('function deliver_mail(): string', $printedCode);
        self::assertStringContainsString('#[\\App\\SomeAttribute(callback: deliver_mail(...))]', $printedCode);
        self::assertStringNotContainsString('send_mail', $printedCode);
    }

    /**
     * Creates a renamer from one fixture.
     *
     * @param string $fileName the fixture file name
     * @param string $contents the fixture contents
     */
    private function renamerWithFixture(string $fileName, string $contents): PhpRename
    {
        $srcDirectory = $this->workspace.'/src';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($srcDirectory.'/'.$fileName, $contents);

        return PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $this->workspace.'/member-graph.cache',
        );
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
