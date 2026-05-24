<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Request\ClassConstantRenameRequest;
use PHPUnit\Framework\TestCase;

/**
 * Tests class-constant rename request validation.
 */
final class ClassConstantRenameRequestTest extends TestCase
{
    /**
     * Ensures that valid class-constant rename inputs are stored.
     */
    public function testItStoresValidClassConstantRenameInputs(): void
    {
        $request = new ClassConstantRenameRequest(
            className: self::class,
            constantName: 'OLD_CONSTANT',
            newConstantName: 'NEW_CONSTANT',
        );

        self::assertSame(self::class, $request->className);
        self::assertSame('OLD_CONSTANT', $request->constantName);
        self::assertSame('NEW_CONSTANT', $request->newConstantName);
        self::assertSame('OLD_CONSTANT', $request->oldName());
        self::assertSame('NEW_CONSTANT', $request->newName());
    }

    /**
     * Ensures that empty class names are rejected.
     */
    public function testItRejectsEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "className" rename input cannot be empty.');

        new ClassConstantRenameRequest('', 'OLD_CONSTANT', 'NEW_CONSTANT');
    }

    /**
     * Ensures that empty class-constant names are rejected.
     */
    public function testItRejectsEmptyConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "constantName" rename input cannot be empty.');

        new ClassConstantRenameRequest(self::class, ' ', 'NEW_CONSTANT');
    }

    /**
     * Ensures that empty replacement class-constant names are rejected.
     */
    public function testItRejectsEmptyNewConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newConstantName" rename input cannot be empty.');

        new ClassConstantRenameRequest(self::class, 'OLD_CONSTANT', "\t");
    }

    /**
     * Ensures invalid current class-constant names are rejected.
     */
    public function testItRejectsInvalidConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "constantName" rename input must be a valid PHP identifier.');

        new ClassConstantRenameRequest(self::class, '123_OLD_CONSTANT', 'NEW_CONSTANT');
    }

    /**
     * Ensures invalid replacement class-constant names are rejected.
     */
    public function testItRejectsInvalidNewConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newConstantName" rename input must be a valid PHP identifier.');

        new ClassConstantRenameRequest(self::class, 'OLD_CONSTANT', '123_NEW_CONSTANT');
    }
}
