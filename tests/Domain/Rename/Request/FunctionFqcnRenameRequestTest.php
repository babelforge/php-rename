<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Request\FunctionFqcnRenameRequest;
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
}
