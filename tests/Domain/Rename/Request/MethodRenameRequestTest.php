<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Request\MethodRenameRequest;
use PHPUnit\Framework\TestCase;

/**
 * Tests method rename request validation.
 */
final class MethodRenameRequestTest extends TestCase
{
    /**
     * Ensures that valid method rename inputs are stored.
     */
    public function testItStoresValidMethodRenameInputs(): void
    {
        $request = new MethodRenameRequest(
            className: self::class,
            methodName: 'oldMethod',
            newMethodName: 'newMethod',
        );

        self::assertSame(self::class, $request->className);
        self::assertSame('oldMethod', $request->methodName);
        self::assertSame('newMethod', $request->newMethodName);
    }

    /**
     * Ensures that empty class names are rejected.
     */
    public function testItRejectsEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "className" rename input cannot be empty.');

        new MethodRenameRequest('', 'oldMethod', 'newMethod');
    }

    /**
     * Ensures that empty method names are rejected.
     */
    public function testItRejectsEmptyMethodName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "methodName" rename input cannot be empty.');

        new MethodRenameRequest(self::class, ' ', 'newMethod');
    }

    /**
     * Ensures that empty replacement method names are rejected.
     */
    public function testItRejectsEmptyNewMethodName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newMethodName" rename input cannot be empty.');

        new MethodRenameRequest(self::class, 'oldMethod', "\t");
    }

    /**
     * Ensures invalid current method names are rejected.
     */
    public function testItRejectsInvalidMethodName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "methodName" rename input must be a valid PHP identifier.');

        new MethodRenameRequest(self::class, '123OldMethod', 'newMethod');
    }

    /**
     * Ensures invalid replacement method names are rejected.
     */
    public function testItRejectsInvalidNewMethodName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newMethodName" rename input must be a valid PHP identifier.');

        new MethodRenameRequest(self::class, 'oldMethod', '123NewMethod');
    }
}
