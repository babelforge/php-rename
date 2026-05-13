<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Integration;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Step\RenameStepContext;
use PhpNoobs\PhpRename\Domain\Rename\Transaction\RenameTransactionStatus;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers nested callable parameter rename planning and AST application.
 */
final class PhpRenameNestedCallableRenameIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-nested-callable-rename-'.str_replace('.', '', uniqid('', true));
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
     * Ensures closure parameter renaming mutates declaration, local usages, and docblocks inside a method container.
     */
    public function testItRenamesClosureParameterInsideMethod(): void
    {
        $renamer = $this->renamerWithFixture('Mailer.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                    /**
                     * @param string $message
                     */
                    $handler = function (string $message): string {
                        $copy = $message;

                        return $message;
                    };
                }
            }
            PHP);

        $result = $renamer->renameClosureParameterInMethod('App\\Mailer', 'send', 0, 'message', 'emailMessage');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(3, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('@param string $emailMessage', $printedCode);
        self::assertStringContainsString('function (string $emailMessage): string', $printedCode);
        self::assertStringContainsString('$copy = $emailMessage;', $printedCode);
        self::assertStringContainsString('return $emailMessage;', $printedCode);
        self::assertStringNotContainsString('@param string $message', $printedCode);
        self::assertStringNotContainsString('function (string $message): string', $printedCode);
    }

    /**
     * Ensures arrow-function parameter renaming mutates declaration and expression usages inside a function container.
     */
    public function testItRenamesArrowFunctionParameterInsideFunction(): void
    {
        $renamer = $this->renamerWithFixture('functions.php', <<<'PHP'
            <?php

            namespace App;

            function map_message(): void
            {
                $mapper = fn (string $message): string => $message;
            }
            PHP);

        $result = $renamer->renameArrowFunctionParameterInFunction('App\\map_message', 0, 'message', 'emailMessage');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertCount(2, $result->plan->operations);
        self::assertCount(0, $result->diagnostics);
        self::assertStringContainsString('fn(string $emailMessage): string => $emailMessage', $printedCode);
        self::assertStringNotContainsString('fn(string $message): string => $message', $printedCode);
    }

    /**
     * Ensures file-level nested callable renames can be executed as orchestration steps.
     */
    public function testItExecutesFileLevelClosureParameterRenameStep(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';
        $filePath = $srcDirectory.'/bootstrap.php';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($filePath, <<<'PHP'
            <?php

            namespace App;

            $handler = function (string $message): string {
                return $message;
            };
            PHP);

        $build = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );
        $renamer = PhpRename::fromBuild($build);

        $stepResult = $renamer->executeStepClosureParameterRenameInFile(
            context: RenameStepContext::fromBuild($build),
            filePath: $filePath,
            closureIndex: 0,
            parameterName: 'message',
            newParameterName: 'emailMessage',
        );
        $printedCode = $this->printedCode($stepResult->context->currentBuild->virtualFiles);

        self::assertTrue($stepResult->applied);
        self::assertCount(2, $stepResult->plan->operations);
        self::assertStringContainsString('function (string $emailMessage): string', $printedCode);
        self::assertStringContainsString('return $emailMessage;', $printedCode);
    }

    /**
     * Ensures nested callable parameter conflicts fail by default and keep virtual files unchanged.
     */
    public function testItFailsNestedCallableParameterRenameWhenLocalVariableAlreadyUsesTheNewName(): void
    {
        $renamer = $this->renamerWithNestedCallableConflictFixture();

        $result = $renamer->renameClosureParameterInMethod('App\\Mailer', 'send', 0, 'message', 'existing');
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertSame(RenameDiagnosticSeverity::ERROR, $this->firstPlanDiagnosticSeverity($result->plan->diagnostics));
        self::assertStringContainsString('function (string $message): string', $printedCode);
        self::assertStringContainsString('$existing = $message;', $printedCode);
        self::assertStringNotContainsString('function (string $existing): string', $printedCode);
    }

    /**
     * Ensures report-only nested callable parameter conflicts keep the plan applicable.
     */
    public function testItReportsNestedCallableParameterRenameConflictWhenPolicyAllowsApplication(): void
    {
        $renamer = $this->renamerWithNestedCallableConflictFixture();

        $result = $renamer->renameClosureParameterInMethod(
            className: 'App\\Mailer',
            methodName: 'send',
            closureIndex: 0,
            parameterName: 'message',
            newParameterName: 'existing',
            conflictPolicy: RenameConflictPolicy::REPORT,
        );
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertSame(RenameDiagnosticSeverity::WARNING, $this->firstPlanDiagnosticSeverity($result->plan->diagnostics));
        self::assertStringContainsString('function (string $existing): string', $printedCode);
        self::assertStringContainsString('$existing = $existing;', $printedCode);
    }

    /**
     * Ensures nested callable parameter renames participate in transactions.
     */
    public function testItRenamesNestedCallableParameterWithinTransaction(): void
    {
        $renamer = $this->renamerWithFixture('Mailer.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                    $mapper = fn (string $message): string => $message;
                }
            }
            PHP);

        $transaction = $renamer->beginTransaction();
        $transaction->renameArrowFunctionParameterInMethod('App\\Mailer', 'send', 0, 'message', 'emailMessage');

        $result = $transaction->commit();
        $printedCode = $this->printedCode($result->virtualFiles);

        self::assertSame(RenameTransactionStatus::COMMITTED, $result->status);
        self::assertCount(1, $result->actionResults);
        self::assertStringContainsString('fn(string $emailMessage): string => $emailMessage', $printedCode);
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
     * Creates a renamer with a nested callable conflict fixture.
     */
    private function renamerWithNestedCallableConflictFixture(): PhpRename
    {
        return $this->renamerWithFixture('Mailer.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                    $handler = function (string $message): string {
                        $existing = $message;

                        return $existing;
                    };
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
