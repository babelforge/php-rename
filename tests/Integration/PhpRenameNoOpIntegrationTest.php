<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Integration;

use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenameResult;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers no-op rename planning across public rename APIs.
 */
final class PhpRenameNoOpIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-no-op-'.str_replace('.', '', uniqid('', true));
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
     * Ensures method no-op renames produce an empty warning plan.
     */
    public function testItHandlesMethodNoOpRename(): void
    {
        $renamer = $this->renamerWithFixture('MethodFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                }
            }
            PHP);

        $this->assertNoOpResult($renamer->renameMethod('App\\Mailer', 'send', 'send'));
    }

    /**
     * Ensures property no-op renames produce an empty warning plan.
     */
    public function testItHandlesPropertyNoOpRename(): void
    {
        $renamer = $this->renamerWithFixture('PropertyFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public string $transport = 'smtp';
            }
            PHP);

        $this->assertNoOpResult($renamer->renameProperty('App\\Mailer', 'transport', 'transport'));
    }

    /**
     * Ensures class-constant no-op renames produce an empty warning plan.
     */
    public function testItHandlesClassConstantNoOpRename(): void
    {
        $renamer = $this->renamerWithFixture('ConstantFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public const DEFAULT_TRANSPORT = 'smtp';
            }
            PHP);

        $this->assertNoOpResult($renamer->renameClassConstant('App\\Mailer', 'DEFAULT_TRANSPORT', 'DEFAULT_TRANSPORT'));
    }

    /**
     * Ensures short class no-op renames produce an empty warning plan.
     */
    public function testItHandlesClassNoOpRename(): void
    {
        $renamer = $this->renamerWithFixture('ClassFixture.php', <<<'PHP'
            <?php

            namespace App;

            class Mailer
            {
            }
            PHP);

        $this->assertNoOpResult($renamer->renameClass('App\\Mailer', 'Mailer'));
    }

    /**
     * Ensures class FQCN no-op renames produce an empty warning plan.
     */
    public function testItHandlesClassFqcnNoOpRename(): void
    {
        $renamer = $this->renamerWithFixture('ClassFqcnFixture.php', <<<'PHP'
            <?php

            namespace App;

            class Mailer
            {
            }
            PHP);

        $this->assertNoOpResult($renamer->renameClassFqcn('App\\Mailer', 'App\\Mailer'));
    }

    /**
     * Ensures short function no-op renames produce an empty warning plan.
     */
    public function testItHandlesFunctionNoOpRename(): void
    {
        $renamer = $this->renamerWithFixture('functions.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(): string
            {
                return 'sent';
            }
            PHP);

        $this->assertNoOpResult($renamer->renameFunction('App\\send_mail', 'send_mail'));
    }

    /**
     * Ensures function FQCN no-op renames produce an empty warning plan.
     */
    public function testItHandlesFunctionFqcnNoOpRename(): void
    {
        $renamer = $this->renamerWithFixture('function_fqcn.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(): string
            {
                return 'sent';
            }
            PHP);

        $this->assertNoOpResult($renamer->renameFunctionFqcn('App\\send_mail', 'App\\send_mail'));
    }

    /**
     * Ensures parameter no-op renames produce an empty warning plan.
     */
    public function testItHandlesParameterNoOpRename(): void
    {
        $renamer = $this->renamerWithFixture('ParameterFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(string $message): void
                {
                }
            }
            PHP);

        $this->assertNoOpResult($renamer->renameMethodParameter('App\\Mailer', 'send', 'message', 'message', 0));
    }

    /**
     * Creates a renamer from one source fixture.
     *
     * @param string $fileName the source file name
     * @param string $contents the source file contents
     */
    private function renamerWithFixture(string $fileName, string $contents): PhpRename
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($srcDirectory.'/'.$fileName, $contents);

        return PhpRename::fromDirectory([$srcDirectory], $cacheFilePath);
    }

    /**
     * Asserts that a rename result is a no-op warning result.
     *
     * @param RenameResult $result the rename result
     */
    private function assertNoOpResult(RenameResult $result): void
    {
        self::assertCount(0, $result->plan->operations);
        self::assertSame(RenameDiagnosticSeverity::WARNING, $this->firstPlanDiagnosticSeverity($result->plan->diagnostics));
        self::assertSame(0, $this->updatedVirtualFileCount($result->virtualFiles));
    }

    /**
     * Returns the first plan diagnostic severity.
     *
     * @param RenameDiagnosticCollection $diagnostics the plan diagnostics
     */
    private function firstPlanDiagnosticSeverity(RenameDiagnosticCollection $diagnostics): ?RenameDiagnosticSeverity
    {
        foreach ($diagnostics as $diagnostic) {
            return $diagnostic->severity;
        }

        return null;
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
