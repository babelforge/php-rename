<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Integration;

use PhpNoobs\PhpRename\Application\PhpRename;
use PhpNoobs\PhpRename\Domain\Rename\Conflict\RenameConflictPolicy;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticCollection;
use PhpNoobs\PhpRename\Domain\Rename\Diagnostic\RenameDiagnosticSeverity;
use PhpNoobs\PhpRename\Domain\Rename\Plan\RenameResult;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PHPUnit\Framework\TestCase;

/**
 * Covers rename conflict policy against real member-graph scope facts.
 */
final class PhpRenameConflictPolicyIntegrationTest extends TestCase
{
    private string $workspace;

    /**
     * Creates a temporary integration workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/php-rename-conflict-policy-'.str_replace('.', '', uniqid('', true));
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
     * Ensures method conflicts block application by default and can be reported as warnings.
     */
    public function testItHandlesMethodRenameConflicts(): void
    {
        $renamer = $this->renamerWithFixture('MethodFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                }

                public function deliver(): void
                {
                }
            }
            PHP);

        $this->assertConflictBlocksApplication($renamer->renameMethod('App\\Mailer', 'send', 'deliver'), 'function send(');
        $this->assertConflictReportsAndApplies($renamer->renameMethod('App\\Mailer', 'send', 'deliver', RenameConflictPolicy::REPORT), 'function deliver(');
    }

    /**
     * Ensures property conflicts block application by default and can be reported as warnings.
     */
    public function testItHandlesPropertyRenameConflicts(): void
    {
        $renamer = $this->renamerWithFixture('PropertyFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public string $transport = 'smtp';

                public string $mailerTransport = 'api';
            }
            PHP);

        $this->assertConflictBlocksApplication($renamer->renameProperty('App\\Mailer', 'transport', 'mailerTransport'), 'public string $transport');
        $this->assertConflictReportsAndApplies($renamer->renameProperty('App\\Mailer', 'transport', 'mailerTransport', RenameConflictPolicy::REPORT), 'public string $mailerTransport');
    }

    /**
     * Ensures class-constant conflicts block application by default and can be reported as warnings.
     */
    public function testItHandlesClassConstantRenameConflicts(): void
    {
        $renamer = $this->renamerWithFixture('ConstantFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public const DEFAULT_TRANSPORT = 'smtp';

                public const FALLBACK_TRANSPORT = 'api';
            }
            PHP);

        $this->assertConflictBlocksApplication($renamer->renameClassConstant('App\\Mailer', 'DEFAULT_TRANSPORT', 'FALLBACK_TRANSPORT'), 'DEFAULT_TRANSPORT');
        $this->assertConflictReportsAndApplies($renamer->renameClassConstant('App\\Mailer', 'DEFAULT_TRANSPORT', 'FALLBACK_TRANSPORT', RenameConflictPolicy::REPORT), 'FALLBACK_TRANSPORT');
    }

    /**
     * Ensures short class-like conflicts block application by default and can be reported as warnings.
     */
    public function testItHandlesClassRenameConflicts(): void
    {
        $renamer = $this->renamerWithFixture('ClassFixture.php', <<<'PHP'
            <?php

            namespace App;

            class Mailer
            {
            }

            class TransactionalMailer
            {
            }
            PHP);

        $this->assertConflictBlocksApplication($renamer->renameClass('App\\Mailer', 'TransactionalMailer'), 'class Mailer');
        $this->assertConflictReportsAndApplies($renamer->renameClass('App\\Mailer', 'TransactionalMailer', RenameConflictPolicy::REPORT), 'class TransactionalMailer');
    }

    /**
     * Ensures class FQCN conflicts block application by default and can be reported as warnings.
     */
    public function testItHandlesClassFqcnRenameConflicts(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/App/Infrastructure', 0o777, true);
        file_put_contents($srcDirectory.'/App/Mailer.php', <<<'PHP'
            <?php

            namespace App;

            class Mailer
            {
            }
            PHP);
        file_put_contents($srcDirectory.'/App/Infrastructure/Sender.php', <<<'PHP'
            <?php

            namespace App\Infrastructure;

            class Sender
            {
            }
            PHP);

        $renamer = PhpRename::fromDirectory([$srcDirectory], $cacheFilePath);

        $this->assertConflictBlocksApplication($renamer->renameClassFqcn('App\\Mailer', 'App\\Infrastructure\\Sender'), 'namespace App;');
        $this->assertConflictReportsAndApplies($renamer->renameClassFqcn('App\\Mailer', 'App\\Infrastructure\\Sender', RenameConflictPolicy::REPORT), 'namespace App\\Infrastructure;');
    }

    /**
     * Ensures class FQCN import alias conflicts are reported before application.
     */
    public function testItHandlesClassFqcnImportAliasConflicts(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/Controller', 0o777, true);
        file_put_contents($srcDirectory.'/App/Mailer.php', <<<'PHP'
            <?php

            namespace App;

            class Mailer
            {
            }
            PHP);
        file_put_contents($srcDirectory.'/Controller/Consumer.php', <<<'PHP'
            <?php

            namespace Controller;

            use App\Mailer;
            use Other\Sender;

            final class Consumer
            {
                public function create(): Mailer
                {
                    return new Mailer();
                }
            }
            PHP);

        $renamer = PhpRename::fromDirectory([$srcDirectory], $cacheFilePath);

        $this->assertConflictBlocksApplication($renamer->renameClassFqcn('App\\Mailer', 'Tools\\Sender'), 'new \\App\\Mailer()');
        $this->assertConflictReportsAndApplies($renamer->renameClassFqcn('App\\Mailer', 'Tools\\Sender', RenameConflictPolicy::REPORT), 'new \\Tools\\Sender()');
    }

    /**
     * Ensures grouped class-like import alias conflicts are reported before application.
     */
    public function testItHandlesClassFqcnGroupedImportAliasConflicts(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/Controller', 0o777, true);
        file_put_contents($srcDirectory.'/App/Mailer.php', <<<'PHP'
            <?php

            namespace App;

            class Mailer
            {
            }
            PHP);
        file_put_contents($srcDirectory.'/Controller/Consumer.php', <<<'PHP'
            <?php

            namespace Controller;

            use App\Mailer;
            use Other\{Sender, Logger};

            final class Consumer
            {
                public function create(): Mailer
                {
                    return new Mailer();
                }
            }
            PHP);

        $renamer = PhpRename::fromDirectory([$srcDirectory], $cacheFilePath);

        $this->assertConflictBlocksApplication($renamer->renameClassFqcn('App\\Mailer', 'Tools\\Sender'), 'new \\App\\Mailer()');
        $this->assertConflictReportsAndApplies($renamer->renameClassFqcn('App\\Mailer', 'Tools\\Sender', RenameConflictPolicy::REPORT), 'new \\Tools\\Sender()');
    }

    /**
     * Ensures explicit class-like import alias conflicts are reported before application.
     */
    public function testItHandlesClassFqcnExplicitImportAliasConflicts(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/Controller', 0o777, true);
        file_put_contents($srcDirectory.'/App/Mailer.php', <<<'PHP'
            <?php

            namespace App;

            class Mailer
            {
            }
            PHP);
        file_put_contents($srcDirectory.'/Controller/Consumer.php', <<<'PHP'
            <?php

            namespace Controller;

            use App\Mailer;
            use Other\Logger as Sender;

            final class Consumer
            {
                public function create(): Mailer
                {
                    return new Mailer();
                }
            }
            PHP);

        $renamer = PhpRename::fromDirectory([$srcDirectory], $cacheFilePath);

        $this->assertConflictBlocksApplication($renamer->renameClassFqcn('App\\Mailer', 'Tools\\Sender'), 'new \\App\\Mailer()');
        $this->assertConflictReportsAndApplies($renamer->renameClassFqcn('App\\Mailer', 'Tools\\Sender', RenameConflictPolicy::REPORT), 'new \\Tools\\Sender()');
    }

    /**
     * Ensures short function conflicts block application by default and can be reported as warnings.
     */
    public function testItHandlesFunctionRenameConflicts(): void
    {
        $renamer = $this->renamerWithFixture('functions.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(): string
            {
                return 'sent';
            }

            function deliver_mail(): string
            {
                return 'delivered';
            }
            PHP);

        $this->assertConflictBlocksApplication($renamer->renameFunction('App\\send_mail', 'deliver_mail'), 'function send_mail(');
        $this->assertConflictReportsAndApplies($renamer->renameFunction('App\\send_mail', 'deliver_mail', RenameConflictPolicy::REPORT), 'function deliver_mail(');
    }

    /**
     * Ensures function FQCN conflicts block application by default and can be reported as warnings.
     */
    public function testItHandlesFunctionFqcnRenameConflicts(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/Tools', 0o777, true);
        file_put_contents($srcDirectory.'/App/functions.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(): string
            {
                return 'sent';
            }
            PHP);
        file_put_contents($srcDirectory.'/Tools/functions.php', <<<'PHP'
            <?php

            namespace Tools;

            function deliver_mail(): string
            {
                return 'delivered';
            }
            PHP);

        $renamer = PhpRename::fromDirectory([$srcDirectory], $cacheFilePath);

        $this->assertConflictBlocksApplication($renamer->renameFunctionFqcn('App\\send_mail', 'Tools\\deliver_mail'), 'namespace App;');
        $this->assertConflictReportsAndApplies($renamer->renameFunctionFqcn('App\\send_mail', 'Tools\\deliver_mail', RenameConflictPolicy::REPORT), 'namespace Tools;');
    }

    /**
     * Ensures function FQCN import alias conflicts are reported before application.
     */
    public function testItHandlesFunctionFqcnImportAliasConflicts(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/Controller', 0o777, true);
        file_put_contents($srcDirectory.'/App/functions.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(): string
            {
                return 'sent';
            }
            PHP);
        file_put_contents($srcDirectory.'/Controller/Consumer.php', <<<'PHP'
            <?php

            namespace Controller;

            use function App\send_mail;
            use function Other\deliver_mail;

            final class Consumer
            {
                public function run(): string
                {
                    return send_mail();
                }
            }
            PHP);

        $renamer = PhpRename::fromDirectory([$srcDirectory], $cacheFilePath);

        $this->assertConflictBlocksApplication($renamer->renameFunctionFqcn('App\\send_mail', 'Tools\\deliver_mail'), 'return \\App\\send_mail();');
        $this->assertConflictReportsAndApplies($renamer->renameFunctionFqcn('App\\send_mail', 'Tools\\deliver_mail', RenameConflictPolicy::REPORT), 'return \\Tools\\deliver_mail();');
    }

    /**
     * Ensures grouped function import alias conflicts are reported before application.
     */
    public function testItHandlesFunctionFqcnGroupedImportAliasConflicts(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/Controller', 0o777, true);
        file_put_contents($srcDirectory.'/App/functions.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(): string
            {
                return 'sent';
            }
            PHP);
        file_put_contents($srcDirectory.'/Controller/Consumer.php', <<<'PHP'
            <?php

            namespace Controller;

            use function App\send_mail;
            use function Other\{deliver_mail, log_mail};

            final class Consumer
            {
                public function run(): string
                {
                    return send_mail();
                }
            }
            PHP);

        $renamer = PhpRename::fromDirectory([$srcDirectory], $cacheFilePath);

        $this->assertConflictBlocksApplication($renamer->renameFunctionFqcn('App\\send_mail', 'Tools\\deliver_mail'), 'return \\App\\send_mail();');
        $this->assertConflictReportsAndApplies($renamer->renameFunctionFqcn('App\\send_mail', 'Tools\\deliver_mail', RenameConflictPolicy::REPORT), 'return \\Tools\\deliver_mail();');
    }

    /**
     * Ensures explicit function import alias conflicts are reported before application.
     */
    public function testItHandlesFunctionFqcnExplicitImportAliasConflicts(): void
    {
        $srcDirectory = $this->workspace.'/src';
        $cacheFilePath = $this->workspace.'/member-graph.cache';

        mkdir($srcDirectory.'/App', 0o777, true);
        mkdir($srcDirectory.'/Controller', 0o777, true);
        file_put_contents($srcDirectory.'/App/functions.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(): string
            {
                return 'sent';
            }
            PHP);
        file_put_contents($srcDirectory.'/Controller/Consumer.php', <<<'PHP'
            <?php

            namespace Controller;

            use function App\send_mail;
            use function Other\log_mail as deliver_mail;

            final class Consumer
            {
                public function run(): string
                {
                    return send_mail();
                }
            }
            PHP);

        $renamer = PhpRename::fromDirectory([$srcDirectory], $cacheFilePath);

        $this->assertConflictBlocksApplication($renamer->renameFunctionFqcn('App\\send_mail', 'Tools\\deliver_mail'), 'return \\App\\send_mail();');
        $this->assertConflictReportsAndApplies($renamer->renameFunctionFqcn('App\\send_mail', 'Tools\\deliver_mail', RenameConflictPolicy::REPORT), 'return \\Tools\\deliver_mail();');
    }

    /**
     * Ensures parameter conflicts are covered by the shared conflict policy.
     */
    public function testItHandlesParameterRenameConflicts(): void
    {
        $renamer = $this->renamerWithFixture('ParameterFixture.php', <<<'PHP'
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

        $this->assertConflictBlocksApplication($renamer->renameMethodParameter('App\\Mailer', 'send', 'message', 'existing', 0), '$existing = $message;');
        $this->assertConflictReportsAndApplies($renamer->renameMethodParameter('App\\Mailer', 'send', 'message', 'existing', 0, RenameConflictPolicy::REPORT), '$existing = $existing;');
    }

    /**
     * Ensures method conflicts are detected case-insensitively.
     */
    public function testItDetectsMethodRenameConflictsCaseInsensitively(): void
    {
        $renamer = $this->renamerWithFixture('MethodCaseFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public function send(): void
                {
                }

                public function deliver(): void
                {
                }
            }
            PHP);

        $this->assertConflictBlocksApplication($renamer->renameMethod('App\\Mailer', 'send', 'DELIVER'), 'function send(');
    }

    /**
     * Ensures class-like conflicts are detected case-insensitively.
     */
    public function testItDetectsClassRenameConflictsCaseInsensitively(): void
    {
        $renamer = $this->renamerWithFixture('ClassCaseFixture.php', <<<'PHP'
            <?php

            namespace App;

            class Mailer
            {
            }

            class TransactionalMailer
            {
            }
            PHP);

        $this->assertConflictBlocksApplication($renamer->renameClass('App\\Mailer', 'transactionalmailer'), 'class Mailer');
    }

    /**
     * Ensures function conflicts are detected case-insensitively.
     */
    public function testItDetectsFunctionRenameConflictsCaseInsensitively(): void
    {
        $renamer = $this->renamerWithFixture('function_case.php', <<<'PHP'
            <?php

            namespace App;

            function send_mail(): string
            {
                return 'sent';
            }

            function deliver_mail(): string
            {
                return 'delivered';
            }
            PHP);

        $this->assertConflictBlocksApplication($renamer->renameFunction('App\\send_mail', 'DELIVER_MAIL'), 'function send_mail(');
    }

    /**
     * Ensures property conflicts remain case-sensitive.
     */
    public function testItKeepsPropertyRenameConflictsCaseSensitive(): void
    {
        $renamer = $this->renamerWithFixture('PropertyCaseFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public string $transport = 'smtp';

                public string $mailerTransport = 'api';
            }
            PHP);

        $this->assertAppliesWithoutConflict($renamer->renameProperty('App\\Mailer', 'transport', 'MAILERTRANSPORT'), 'public string $MAILERTRANSPORT');
    }

    /**
     * Ensures class-constant conflicts remain case-sensitive.
     */
    public function testItKeepsClassConstantRenameConflictsCaseSensitive(): void
    {
        $renamer = $this->renamerWithFixture('ConstantCaseFixture.php', <<<'PHP'
            <?php

            namespace App;

            final class Mailer
            {
                public const DEFAULT_TRANSPORT = 'smtp';

                public const FALLBACK_TRANSPORT = 'api';
            }
            PHP);

        $this->assertAppliesWithoutConflict($renamer->renameClassConstant('App\\Mailer', 'DEFAULT_TRANSPORT', 'fallback_transport'), 'fallback_transport');
    }

    /**
     * Ensures parameter conflicts remain case-sensitive.
     */
    public function testItKeepsParameterRenameConflictsCaseSensitive(): void
    {
        $renamer = $this->renamerWithFixture('ParameterCaseFixture.php', <<<'PHP'
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

        $this->assertAppliesWithoutConflict($renamer->renameMethodParameter('App\\Mailer', 'send', 'message', 'Existing', 0), '$existing = $Existing;');
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
     * Asserts that a conflict blocks application.
     *
     * @param RenameResult $result               the rename result
     * @param string       $expectedOriginalCode the original code fragment expected after the blocked apply
     */
    private function assertConflictBlocksApplication(RenameResult $result, string $expectedOriginalCode): void
    {
        self::assertSame(RenameDiagnosticSeverity::ERROR, $this->firstPlanDiagnosticSeverity($result->plan->diagnostics));
        self::assertSame(0, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString($expectedOriginalCode, $this->printedCode($result->virtualFiles));
    }

    /**
     * Asserts that a conflict is reported while application remains allowed.
     *
     * @param RenameResult $result              the rename result
     * @param string       $expectedAppliedCode the applied code fragment
     */
    private function assertConflictReportsAndApplies(RenameResult $result, string $expectedAppliedCode): void
    {
        self::assertSame(RenameDiagnosticSeverity::WARNING, $this->firstPlanDiagnosticSeverity($result->plan->diagnostics));
        self::assertGreaterThan(0, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString($expectedAppliedCode, $this->printedCode($result->virtualFiles));
    }

    /**
     * Asserts that a rename applies without conflict diagnostics.
     *
     * @param RenameResult $result              the rename result
     * @param string       $expectedAppliedCode the applied code fragment
     */
    private function assertAppliesWithoutConflict(RenameResult $result, string $expectedAppliedCode): void
    {
        self::assertCount(0, $result->plan->diagnostics);
        self::assertGreaterThan(0, $this->updatedVirtualFileCount($result->virtualFiles));
        self::assertStringContainsString($expectedAppliedCode, $this->printedCode($result->virtualFiles));
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
