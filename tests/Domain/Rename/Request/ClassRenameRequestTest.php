<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Request\ClassRenameRequest;
use PHPUnit\Framework\TestCase;

/**
 * Covers class-like owner rename request validation.
 */
final class ClassRenameRequestTest extends TestCase
{
    /**
     * Ensures the request exposes short current and replacement names.
     */
    public function testItExposesRenameNames(): void
    {
        $request = new ClassRenameRequest('App\\Mailer', 'TransactionalMailer');

        self::assertSame('Mailer', $request->oldName());
        self::assertSame('TransactionalMailer', $request->newName());
    }

    /**
     * Ensures empty current class-like owner names are rejected.
     */
    public function testItRejectsEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "className" rename input cannot be empty.');

        new ClassRenameRequest('', 'TransactionalMailer');
    }

    /**
     * Ensures empty replacement class-like owner names are rejected.
     */
    public function testItRejectsEmptyNewClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newClassName" rename input cannot be empty.');

        new ClassRenameRequest('App\\Mailer', '');
    }

    /**
     * Ensures namespace moves are rejected for the first class rename slice.
     */
    public function testItRejectsNamespacedNewClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newClassName" rename input must be a short name.');

        new ClassRenameRequest('App\\Mailer', 'App\\TransactionalMailer');
    }

    /**
     * Ensures invalid class-like owner names are rejected.
     */
    public function testItRejectsInvalidClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "className" rename input must be a valid PHP FQCN.');

        new ClassRenameRequest('App\\\\Mailer', 'TransactionalMailer');
    }

    /**
     * Ensures invalid replacement class-like owner names are rejected.
     */
    public function testItRejectsInvalidNewClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newClassName" rename input must be a valid PHP identifier.');

        new ClassRenameRequest('App\\Mailer', '123Mailer');
    }
}
