<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Request\EnumCaseRenameRequest;
use PHPUnit\Framework\TestCase;

/**
 * Tests enum-case rename request validation.
 */
final class EnumCaseRenameRequestTest extends TestCase
{
    /**
     * Ensures that valid enum-case rename inputs are stored.
     */
    public function testItStoresValidEnumCaseRenameInputs(): void
    {
        $request = new EnumCaseRenameRequest(
            enumName: self::class,
            caseName: 'ACTIVE',
            newCaseName: 'ENABLED',
        );

        self::assertSame(self::class, $request->enumName);
        self::assertSame('ACTIVE', $request->caseName);
        self::assertSame('ENABLED', $request->newCaseName);
        self::assertSame('ACTIVE', $request->oldName());
        self::assertSame('ENABLED', $request->newName());
    }

    /**
     * Ensures that empty enum names are rejected.
     */
    public function testItRejectsEmptyEnumName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "enumName" rename input cannot be empty.');

        new EnumCaseRenameRequest('', 'ACTIVE', 'ENABLED');
    }

    /**
     * Ensures that empty enum-case names are rejected.
     */
    public function testItRejectsEmptyCaseName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "caseName" rename input cannot be empty.');

        new EnumCaseRenameRequest(self::class, ' ', 'ENABLED');
    }

    /**
     * Ensures that empty replacement enum-case names are rejected.
     */
    public function testItRejectsEmptyNewCaseName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newCaseName" rename input cannot be empty.');

        new EnumCaseRenameRequest(self::class, 'ACTIVE', "\t");
    }

    /**
     * Ensures invalid current enum-case names are rejected.
     */
    public function testItRejectsInvalidCaseName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "caseName" rename input must be a valid PHP identifier.');

        new EnumCaseRenameRequest(self::class, '123_ACTIVE', 'ENABLED');
    }

    /**
     * Ensures invalid replacement enum-case names are rejected.
     */
    public function testItRejectsInvalidNewCaseName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newCaseName" rename input must be a valid PHP identifier.');

        new EnumCaseRenameRequest(self::class, 'ACTIVE', '123_ENABLED');
    }
}
