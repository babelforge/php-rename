<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Integration;

use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers class-like owner FQCN rename planning and AST application against real member-graph builds.
 */
final class PhpRenameClassFqcnRenameIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-class-fqcn-rename-'.str_replace('.', '', uniqid('', true));
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
     * Ensures class-like owner FQCN renaming mutates declaration namespace, declaration name, usages, and docblocks.
     */
    public function testItRenamesClassDeclarationNamespaceUsagesAndSupportedDocblockReferences(): void
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

        $result = $renamer->renameClassFqcn('App\\Mailer', 'App\\Infrastructure\\Sender');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertGreaterThanOrEqual(4, count($result->plan->operations));
        self::assertCount(0, $result->diagnostics);
        self::assertSame(3, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('namespace App\\Infrastructure;', $printedCode);
        self::assertStringContainsString('class Sender', $printedCode);
        self::assertStringContainsString('use App\\Infrastructure\\Sender;', $printedCode);
        self::assertStringContainsString('extends Sender', $printedCode);
        self::assertStringContainsString('new Sender()', $printedCode);
        self::assertStringContainsString('Sender::class', $printedCode);
        self::assertStringContainsString('@see App\\Infrastructure\\Sender', $printedCode);
        self::assertStringContainsString('Mentions Mailer in prose without changing free text.', $printedCode);
        self::assertStringNotContainsString('class Mailer', $printedCode);
        self::assertStringNotContainsString('\\App\\Mailer::class', $printedCode);
        self::assertStringNotContainsString('@see App\\Mailer', $printedCode);
    }

    /**
     * Ensures class FQCN renaming updates declarations, inheritance, implementations, and multiple consumers.
     */
    public function testItRenamesClassFqcnAcrossInterfacesChildAndConsumers(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/Func', 0o777, true);
        mkdir($srcDirectory.'/Main', 0o777, true);
        mkdir($srcDirectory.'/Controller', 0o777, true);
        mkdir($srcDirectory.'/Admin', 0o777, true);

        $this->writeInterface1File($srcDirectory.'/App/Interface1.php');
        $this->writeInterface2File($srcDirectory.'/App/Interface2.php');
        $this->writeClass1File($srcDirectory.'/Func/Class1.php');
        $this->writeSameNamespaceConsumerFile($srcDirectory.'/Func/SameNamespaceConsumer.php');
        $this->writeChildClass2File($srcDirectory.'/Main/ChildClass2.php');
        $this->writeConsumer1File($srcDirectory.'/Controller/Consumer1.php');
        $this->writeConsumer2File($srcDirectory.'/Admin/Consumer2.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameClassFqcn('Func\\Class1', 'Proto\\Protocol');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertGreaterThanOrEqual(7, count($result->plan->operations));
        self::assertCount(0, $result->diagnostics);
        self::assertSame(5, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('namespace Proto;', $printedCode);
        self::assertStringContainsString('class Protocol implements \\App\\Interface1, \\App\\Interface2', $printedCode);
        self::assertStringContainsString('use Proto\\Protocol;', $printedCode);
        self::assertStringContainsString('final class ChildClass2 extends Protocol', $printedCode);
        self::assertStringContainsString('final class SameNamespaceConsumer', $printedCode);
        self::assertStringContainsString('public function create(): Protocol', $printedCode);
        self::assertStringContainsString('public function handle(Protocol $protocol): Protocol', $printedCode);
        self::assertStringContainsString('return new Protocol();', $printedCode);
        self::assertStringContainsString('public function inspect(Protocol $protocol): string', $printedCode);
        self::assertStringContainsString('return Protocol::class;', $printedCode);
        self::assertStringContainsString('@see Proto\\Protocol', $printedCode);
        self::assertStringNotContainsString('class Class1', $printedCode);
        self::assertStringNotContainsString('\\Func\\Class1', $printedCode);
        self::assertStringNotContainsString('use Func\\Class1;', $printedCode);
        self::assertStringNotContainsString('@see Func\\Class1', $printedCode);
    }

    /**
     * Ensures class FQCN renaming preserves explicit aliases from grouped imports.
     */
    public function testItPreservesExplicitAliasWhenClassFqcnRenameUpdatesGroupedImport(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/Controller', 0o777, true);

        $this->writeMailerFile($srcDirectory.'/App/Mailer.php');
        file_put_contents($srcDirectory.'/Controller/Consumer.php', <<<'PHP'
            <?php

            namespace Controller;

            use App\{Mailer as LegacyMailer};

            final class Consumer
            {
                public function create(): LegacyMailer
                {
                    return new LegacyMailer();
                }
            }
            PHP);

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameClassFqcn('App\\Mailer', 'Tools\\Sender');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('use Tools\\Sender as LegacyMailer;', $printedCode);
        self::assertStringContainsString('public function create(): LegacyMailer', $printedCode);
        self::assertStringContainsString('return new LegacyMailer();', $printedCode);
        self::assertStringNotContainsString('use App\\{Tools\\Sender', $printedCode);
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
             * @see App\Mailer
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
     * Writes the first interface fixture.
     *
     * @param string $filePath the file path
     */
    private function writeInterface1File(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            interface Interface1
            {
            }
            PHP);
    }

    /**
     * Writes the second interface fixture.
     *
     * @param string $filePath the file path
     */
    private function writeInterface2File(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            interface Interface2
            {
            }
            PHP);
    }

    /**
     * Writes the class fixture targeted by the FQCN rename.
     *
     * @param string $filePath the file path
     */
    private function writeClass1File(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace Func;

            use App\Interface1;
            use App\Interface2;

            /**
             * @see Func\Class1
             */
            class Class1 implements Interface1, Interface2
            {
            }
            PHP);
    }

    /**
     * Writes the child class fixture.
     *
     * @param string $filePath the file path
     */
    private function writeChildClass2File(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace Main;

            use Func\Class1;

            final class ChildClass2 extends Class1
            {
            }
            PHP);
    }

    /**
     * Writes a consumer fixture that uses the target class from the same namespace without an import.
     *
     * @param string $filePath the file path
     */
    private function writeSameNamespaceConsumerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace Func;

            final class SameNamespaceConsumer
            {
                public function create(): Class1
                {
                    return new Class1();
                }
            }
            PHP);
    }

    /**
     * Writes the first consumer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeConsumer1File(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace Controller;

            use Func\Class1;

            final class Consumer1
            {
                public function handle(Class1 $protocol): Class1
                {
                    return new Class1();
                }
            }
            PHP);
    }

    /**
     * Writes the second consumer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeConsumer2File(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace Admin;

            use Func\Class1;

            final class Consumer2
            {
                public function inspect(Class1 $protocol): string
                {
                    return Class1::class;
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
