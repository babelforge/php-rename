<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Request\ConstantRenameRequest;
use PHPUnit\Framework\TestCase;

/**
 * Tests namespace-level constant rename request validation.
 */
final class ConstantRenameRequestTest extends TestCase
{
    /**
     * Ensures that valid constant rename inputs are stored.
     */
    public function testItStoresValidConstantRenameInputs(): void
    {
        $request = new ConstantRenameRequest(
            constantName: 'App\\Config\\ENABLED',
            newConstantName: 'ACTIVE',
        );

        self::assertSame('App\\Config\\ENABLED', $request->constantName);
        self::assertSame('ACTIVE', $request->newConstantName);
        self::assertSame('ENABLED', $request->oldName());
        self::assertSame('ACTIVE', $request->newName());
    }

    /**
     * Ensures that empty constant names are rejected.
     */
    public function testItRejectsEmptyConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "constantName" rename input cannot be empty.');

        new ConstantRenameRequest('', 'ACTIVE');
    }

    /**
     * Ensures that empty replacement constant names are rejected.
     */
    public function testItRejectsEmptyNewConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newConstantName" rename input cannot be empty.');

        new ConstantRenameRequest('App\\Config\\ENABLED', "\t");
    }

    /**
     * Ensures that replacement constant names must be short names.
     */
    public function testItRejectsNamespacedNewConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newConstantName" rename input must be a short name.');

        new ConstantRenameRequest('App\\Config\\ENABLED', 'App\\Config\\ACTIVE');
    }

    /**
     * Ensures invalid constant names are rejected.
     */
    public function testItRejectsInvalidConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "constantName" rename input must be a valid PHP FQCN.');

        new ConstantRenameRequest('App\\Config\\123_ENABLED', 'ACTIVE');
    }

    /**
     * Ensures invalid replacement constant names are rejected.
     */
    public function testItRejectsInvalidNewConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newConstantName" rename input must be a valid PHP identifier.');

        new ConstantRenameRequest('App\\Config\\ENABLED', '123_ACTIVE');
    }
}
