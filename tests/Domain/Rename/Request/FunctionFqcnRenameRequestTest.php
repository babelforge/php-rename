<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Request\FunctionFqcnRenameRequest;
use PHPUnit\Framework\TestCase;

/**
 * Covers fully-qualified function rename request validation.
 */
final class FunctionFqcnRenameRequestTest extends TestCase
{
    /**
     * Ensures the request exposes fully-qualified current and replacement names.
     */
    public function testItExposesRenameNames(): void
    {
        $request = new FunctionFqcnRenameRequest('App\\send_mail', 'Tools\\deliver_mail');

        self::assertSame('App\\send_mail', $request->oldName());
        self::assertSame('Tools\\deliver_mail', $request->newName());
    }

    /**
     * Ensures empty current function names are rejected.
     */
    public function testItRejectsEmptyFunctionName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "functionName" rename input cannot be empty.');

        new FunctionFqcnRenameRequest('', 'Tools\\deliver_mail');
    }

    /**
     * Ensures empty replacement function names are rejected.
     */
    public function testItRejectsEmptyNewFunctionName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newFunctionName" rename input cannot be empty.');

        new FunctionFqcnRenameRequest('App\\send_mail', '');
    }

    /**
     * Ensures invalid current function FQCNs are rejected.
     */
    public function testItRejectsInvalidFunctionName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "functionName" rename input must be a valid PHP FQCN.');

        new FunctionFqcnRenameRequest('App\\123_send_mail', 'Tools\\deliver_mail');
    }

    /**
     * Ensures invalid replacement function FQCNs are rejected.
     */
    public function testItRejectsInvalidNewFunctionName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newFunctionName" rename input must be a valid PHP FQCN.');

        new FunctionFqcnRenameRequest('App\\send_mail', 'Tools\\123_deliver_mail');
    }
}
