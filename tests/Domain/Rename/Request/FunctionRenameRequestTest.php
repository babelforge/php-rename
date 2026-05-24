<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Request\FunctionRenameRequest;
use PHPUnit\Framework\TestCase;

/**
 * Tests function rename request validation.
 */
final class FunctionRenameRequestTest extends TestCase
{
    /**
     * Ensures that valid function rename inputs are stored.
     */
    public function testItStoresValidFunctionRenameInputs(): void
    {
        $request = new FunctionRenameRequest(
            functionName: 'App\\send_mail',
            newFunctionName: 'deliver_mail',
        );

        self::assertSame('App\\send_mail', $request->functionName);
        self::assertSame('deliver_mail', $request->newFunctionName);
        self::assertSame('send_mail', $request->oldName());
        self::assertSame('deliver_mail', $request->newName());
    }

    /**
     * Ensures that empty function names are rejected.
     */
    public function testItRejectsEmptyFunctionName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "functionName" rename input cannot be empty.');

        new FunctionRenameRequest('', 'deliver_mail');
    }

    /**
     * Ensures that empty replacement function names are rejected.
     */
    public function testItRejectsEmptyNewFunctionName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newFunctionName" rename input cannot be empty.');

        new FunctionRenameRequest('App\\send_mail', "\t");
    }

    /**
     * Ensures that replacement function names must be short names.
     */
    public function testItRejectsNamespacedNewFunctionName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newFunctionName" rename input must be a short name.');

        new FunctionRenameRequest('App\\send_mail', 'App\\deliver_mail');
    }

    /**
     * Ensures invalid function names are rejected.
     */
    public function testItRejectsInvalidFunctionName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "functionName" rename input must be a valid PHP FQCN.');

        new FunctionRenameRequest('App\\123_send_mail', 'deliver_mail');
    }

    /**
     * Ensures invalid replacement function names are rejected.
     */
    public function testItRejectsInvalidNewFunctionName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newFunctionName" rename input must be a valid PHP identifier.');

        new FunctionRenameRequest('App\\send_mail', '123_deliver_mail');
    }
}
