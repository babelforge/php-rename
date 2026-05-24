<?php

declare(strict_types=1);

namespace BabelForge\PhpRename\Tests\Domain\Rename\Request;

use BabelForge\PhpRename\Domain\Rename\Request\PropertyRenameRequest;
use PHPUnit\Framework\TestCase;

/**
 * Tests property rename request validation.
 */
final class PropertyRenameRequestTest extends TestCase
{
    /**
     * Ensures that valid property rename inputs are stored.
     */
    public function testItStoresValidPropertyRenameInputs(): void
    {
        $request = new PropertyRenameRequest(
            className: self::class,
            propertyName: 'oldProperty',
            newPropertyName: 'newProperty',
        );

        self::assertSame(self::class, $request->className);
        self::assertSame('oldProperty', $request->propertyName);
        self::assertSame('newProperty', $request->newPropertyName);
        self::assertSame('oldProperty', $request->oldName());
        self::assertSame('newProperty', $request->newName());
    }

    /**
     * Ensures that empty class names are rejected.
     */
    public function testItRejectsEmptyClassName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "className" rename input cannot be empty.');

        new PropertyRenameRequest('', 'oldProperty', 'newProperty');
    }

    /**
     * Ensures that empty property names are rejected.
     */
    public function testItRejectsEmptyPropertyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "propertyName" rename input cannot be empty.');

        new PropertyRenameRequest(self::class, ' ', 'newProperty');
    }

    /**
     * Ensures that empty replacement property names are rejected.
     */
    public function testItRejectsEmptyNewPropertyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newPropertyName" rename input cannot be empty.');

        new PropertyRenameRequest(self::class, 'oldProperty', "\t");
    }

    /**
     * Ensures invalid current property names are rejected.
     */
    public function testItRejectsInvalidPropertyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "propertyName" rename input must be a valid PHP identifier.');

        new PropertyRenameRequest(self::class, '$oldProperty', 'newProperty');
    }

    /**
     * Ensures invalid replacement property names are rejected.
     */
    public function testItRejectsInvalidNewPropertyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "newPropertyName" rename input must be a valid PHP identifier.');

        new PropertyRenameRequest(self::class, 'oldProperty', '123NewProperty');
    }
}
