<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRename\Domain\Rename\Validation;

/**
 * Validates rename input names before planning.
 */
final readonly class RenameInputValidator
{
    /**
     * Ensures that one input is not empty.
     *
     * @param string $value the input value
     * @param string $name  the input name
     *
     * @throws \InvalidArgumentException when the input is empty
     */
    public static function guardNotEmpty(string $value, string $name): void
    {
        if ('' === trim($value)) {
            throw new \InvalidArgumentException(sprintf('The "%s" rename input cannot be empty.', $name));
        }
    }

    /**
     * Ensures that one input is a valid short PHP identifier.
     *
     * @param string $value the input value
     * @param string $name  the input name
     *
     * @throws \InvalidArgumentException when the input is not a valid identifier
     */
    public static function guardShortIdentifier(string $value, string $name): void
    {
        self::guardNotEmpty($value, $name);

        if (str_contains($value, '\\')) {
            throw new \InvalidArgumentException(sprintf('The "%s" rename input must be a short name.', $name));
        }

        if (!self::isIdentifier($value)) {
            throw new \InvalidArgumentException(sprintf('The "%s" rename input must be a valid PHP identifier.', $name));
        }
    }

    /**
     * Ensures that one input is a valid PHP FQCN-like name.
     *
     * @param string $value the input value
     * @param string $name  the input name
     *
     * @throws \InvalidArgumentException when the input is not a valid FQCN-like name
     */
    public static function guardFqcn(string $value, string $name): void
    {
        self::guardNotEmpty($value, $name);

        $trimmedValue = ltrim($value, '\\');

        if ('' === $trimmedValue) {
            throw new \InvalidArgumentException(sprintf('The "%s" rename input must be a valid PHP FQCN.', $name));
        }

        foreach (explode('\\', $trimmedValue) as $part) {
            if (!self::isIdentifier($part)) {
                throw new \InvalidArgumentException(sprintf('The "%s" rename input must be a valid PHP FQCN.', $name));
            }
        }
    }

    /**
     * Ensures that one optional index is zero or greater.
     *
     * @param int|null $value the optional index value
     * @param string   $name  the input name
     *
     * @throws \InvalidArgumentException when the index is negative
     */
    public static function guardParameterIndex(?int $value, string $name): void
    {
        if (null === $value) {
            return;
        }

        self::guardNonNegativeIndex($value, $name);
    }

    /**
     * Ensures that one index is zero or greater.
     *
     * @param int    $value the index value
     * @param string $name  the input name
     *
     * @throws \InvalidArgumentException when the index is negative
     */
    public static function guardNonNegativeIndex(int $value, string $name): void
    {
        if (0 > $value) {
            throw new \InvalidArgumentException(sprintf('The "%s" rename input must be zero or greater.', $name));
        }
    }

    /**
     * Indicates whether one value is a valid PHP identifier.
     *
     * @param string $value the value to inspect
     */
    private static function isIdentifier(string $value): bool
    {
        return 1 === preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value);
    }
}
