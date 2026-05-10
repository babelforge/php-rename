<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Request\ClassFqcnRenameRequest;
use PHPUnit\Framework\TestCase;

/**
 * Covers fully-qualified class-like owner rename request validation.
 */
final class ClassFqcnRenameRequestTest extends TestCase
{
    /**
     * Ensures the request exposes fully-qualified current and replacement names.
     */
    public function testItExposesRenameNames(): void
    {
        $request = new ClassFqcnRenameRequest('App\\Mailer', 'App\\Infrastructure\\Sender');

        self::assertSame('App\\Mailer', $request->oldName());
        self::assertSame('App\\Infrastructure\\Sender', $request->newName());
    }

    /**
     * Ensures empty current class-like owner names are rejected.
     */
    public function testItRejectsEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "className" rename input cannot be empty.');

        new ClassFqcnRenameRequest('', 'App\\Infrastructure\\Sender');
    }

    /**
     * Ensures empty replacement class-like owner names are rejected.
     */
    public function testItRejectsEmptyNewClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newClassName" rename input cannot be empty.');

        new ClassFqcnRenameRequest('App\\Mailer', '');
    }
}
