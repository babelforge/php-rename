<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Tests\Domain\Rename\Request;

use PhpNoobs\PhpRename\Domain\Rename\Request\ConstantFqcnRenameRequest;
use PHPUnit\Framework\TestCase;

/**
 * Covers fully-qualified namespace-level constant rename request validation.
 */
final class ConstantFqcnRenameRequestTest extends TestCase
{
    /**
     * Ensures the request exposes fully-qualified current and replacement names.
     */
    public function testItExposesRenameNames(): void
    {
        $request = new ConstantFqcnRenameRequest('App\\Config\\ENABLED', 'Tools\\ACTIVE');

        self::assertSame('App\\Config\\ENABLED', $request->oldName());
        self::assertSame('Tools\\ACTIVE', $request->newName());
    }

    /**
     * Ensures empty current constant names are rejected.
     */
    public function testItRejectsEmptyConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "constantName" rename input cannot be empty.');

        new ConstantFqcnRenameRequest('', 'Tools\\ACTIVE');
    }

    /**
     * Ensures empty replacement constant names are rejected.
     */
    public function testItRejectsEmptyNewConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newConstantName" rename input cannot be empty.');

        new ConstantFqcnRenameRequest('App\\Config\\ENABLED', '');
    }

    /**
     * Ensures invalid current constant FQCNs are rejected.
     */
    public function testItRejectsInvalidConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "constantName" rename input must be a valid PHP FQCN.');

        new ConstantFqcnRenameRequest('App\\Config\\123_ENABLED', 'Tools\\ACTIVE');
    }

    /**
     * Ensures invalid replacement constant FQCNs are rejected.
     */
    public function testItRejectsInvalidNewConstantName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newConstantName" rename input must be a valid PHP FQCN.');

        new ConstantFqcnRenameRequest('App\\Config\\ENABLED', 'Tools\\123_ACTIVE');
    }
}
