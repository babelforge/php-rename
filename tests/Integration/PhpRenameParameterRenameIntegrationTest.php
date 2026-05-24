<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Integration;

use BabelForge\PhpRename\Application\PhpRename;
use BabelForge\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use BabelForge\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use BabelForge\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers parameter rename planning and AST application against real member-graph builds.
 */
final class PhpRenameParameterRenameIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-parameter-rename-'.str_replace('.', '', uniqid('', true));
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
     * Ensures method parameter renaming mutates declaration, local usages, named arguments, and docblocks.
     */
    public function testItRenamesMethodParameterDeclarationLocalUsagesNamedArgumentsAndDocblock(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeMailerFile($srcDirectory.'/Mailer.php');
        $this->writeMethodParameterRunnerFile($srcDirectory.'/Runner.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameMethodParameter('App\\Mailer', 'send', 'message', 'emailMessage', 0);
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(4, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertSame(2, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('@param string $emailMessage', $printedCode);
        self::assertStringContainsString("array{\n     *     subject: string\n     * } \$emailMessage", $printedCode);
        self::assertStringContainsString('public function send(string $emailMessage, string $transport): void', $printedCode);
        self::assertStringContainsString('$this->store($emailMessage);', $printedCode);
        self::assertStringContainsString('emailMessage: $message', $printedCode);
        self::assertStringContainsString('emailMessage: \'hello\'', $printedCode);
        self::assertStringNotContainsString('@param string $message', $printedCode);
        self::assertStringNotContainsString("array{\n     *     subject: string\n     * } \$message", $printedCode);
        self::assertStringNotContainsString('public function send(string $message, string $transport): void', $printedCode);
        self::assertStringNotContainsString('$this->store($message);', $printedCode);
    }

    /**
     * Ensures function parameter renaming supports the optional declaration index.
     */
    public function testItRenamesFunctionParameterAtIndex(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeFunctionFile($srcDirectory.'/functions.php');
        $this->writeFunctionParameterRunnerFile($srcDirectory.'/FunctionRunner.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameFunctionParameter('App\\send_mail', 'recipient', 'emailRecipient', 1);
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(3, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('@param string $emailRecipient', $printedCode);
        self::assertStringContainsString('function send_mail(string $message, string $emailRecipient): void', $printedCode);
        self::assertStringContainsString('store_recipient($emailRecipient);', $printedCode);
        self::assertStringContainsString('emailRecipient: \'me@example.com\'', $printedCode);
        self::assertStringNotContainsString('@param string $recipient', $printedCode);
        self::assertStringNotContainsString('store_recipient($recipient);', $printedCode);
        self::assertStringNotContainsString('recipient: \'me@example.com\'', $printedCode);
    }

    /**
     * Ensures parameter conflicts fail by default and keep virtual files unchanged.
     */
    public function testItFailsParameterRenameWhenLocalVariableAlreadyUsesTheNewName(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeParameterConflictMailerFile($srcDirectory.'/Mailer.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameMethodParameter('App\\Mailer', 'send', 'message', 'existing', 0);
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertSame(RenameDiagnosticSeverity::ERROR, $this->firstPlanDiagnosticSeverity($result->plan->diagnostics));
        self::assertSame(0, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('public function send(string $message): void', $printedCode);
        self::assertStringContainsString('$existing = $message;', $printedCode);
    }

    /**
     * Ensures parameter conflicts can be reported while keeping the plan applicable.
     */
    public function testItReportsParameterRenameConflictWhenPolicyAllowsApplication(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory, 0o777, true);
        $this->writeParameterConflictMailerFile($srcDirectory.'/Mailer.php');

        $renamer = PhpRename::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $result = $renamer->renameMethodParameter(
            className: 'App\\Mailer',
            methodName: 'send',
            parameterName: 'message',
            newParameterName: 'existing',
            parameterIndex: 0,
            conflictPolicy: RenameConflictPolicy::REPORT,
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertSame(RenameDiagnosticSeverity::WARNING, $this->firstPlanDiagnosticSeverity($result->plan->diagnostics));
        self::assertSame(1, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString('public function send(string $existing): void', $printedCode);
        self::assertStringContainsString('$existing = $existing;', $printedCode);
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
                 * @param string $message
                 * @param array{
                 *     subject: string
                 * } $message
                 * @param string $transport
                 */
                public function send(string $message, string $transport): void
                {
                    $this->store($message);
                }

                private function store(string $message): void
                {
                }
            }
            PHP);
    }

    /**
     * Writes the method parameter runner fixture.
     *
     * @param string $filePath the file path
     */
    private function writeMethodParameterRunnerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Runner
            {
                public function run(Mailer $mailer, string $message): void
                {
                    $mailer->send(message: 'hello', transport: 'smtp');
                    $mailer->send(message: $message, transport: 'smtp');
                }
            }
            PHP);
    }

    /**
     * Writes the function fixture.
     *
     * @param string $filePath the file path
     */
    private function writeFunctionFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            /**
             * @param string $message
             * @param string $recipient
             */
            function send_mail(string $message, string $recipient): void
            {
                store_recipient($recipient);
            }

            function store_recipient(string $recipient): void
            {
            }
            PHP);
    }

    /**
     * Writes the function parameter runner fixture.
     *
     * @param string $filePath the file path
     */
    private function writeFunctionParameterRunnerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class FunctionRunner
            {
                public function run(): void
                {
                    send_mail(message: 'hello', recipient: 'me@example.com');
                }
            }
            PHP);
    }

    /**
     * Writes the parameter conflict mailer fixture.
     *
     * @param string $filePath the file path
     */
    private function writeParameterConflictMailerFile(string $filePath): void
    {
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(string $message): void
                {
                    $existing = $message;
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
