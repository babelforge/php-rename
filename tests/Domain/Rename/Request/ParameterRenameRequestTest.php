<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Request\ParameterRenameRequest;
use PHPUnit\Framework\TestCase;

/**
 * Covers parameter rename request validation.
 */
final class ParameterRenameRequestTest extends TestCase
{
    /**
     * Ensures the request exposes current and replacement names.
     */
    public function testItExposesRenameNames(): void
    {
        $request = new ParameterRenameRequest('App\\Mailer', 'send', 'message', 'emailMessage', 0);

        self::assertSame('message', $request->oldName());
        self::assertSame('emailMessage', $request->newName());
        self::assertSame(0, $request->parameterIndex);
    }

    /**
     * Ensures negative indexes are rejected.
     */
    public function testItRejectsNegativeParameterIndex(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "parameterIndex" rename input must be greater than or equal to zero.');

        new ParameterRenameRequest('App\\Mailer', 'send', 'message', 'emailMessage', -1);
    }

    /**
     * Ensures empty parameter names are rejected.
     */
    public function testItRejectsEmptyParameterName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "parameterName" rename input cannot be empty.');

        new ParameterRenameRequest('App\\Mailer', 'send', '', 'emailMessage');
    }

    /**
     * Ensures invalid current parameter names are rejected.
     */
    public function testItRejectsInvalidParameterName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "parameterName" rename input must be a valid PHP identifier.');

        new ParameterRenameRequest('App\\Mailer', 'send', '$message', 'emailMessage');
    }

    /**
     * Ensures invalid replacement parameter names are rejected.
     */
    public function testItRejectsInvalidNewParameterName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newParameterName" rename input must be a valid PHP identifier.');

        new ParameterRenameRequest('App\\Mailer', 'send', 'message', '123EmailMessage');
    }

    /**
     * Ensures invalid function-like names are rejected.
     */
    public function testItRejectsInvalidFunctionLikeName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "functionLikeName" rename input must be a valid PHP identifier.');

        new ParameterRenameRequest('App\\Mailer', '123send', 'message', 'emailMessage');
    }
}
