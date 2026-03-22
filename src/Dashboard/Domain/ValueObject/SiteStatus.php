<?php

declare(strict_types=1);

namespace App\Dashboard\Domain\ValueObject;

final class SiteStatus
{
    public const ACTIVE   = 'active';
    public const INACTIVE = 'inactive';
    public const PENDING  = 'pending';
    public const ERROR    = 'error';

    private const ALLOWED = [self::ACTIVE, self::INACTIVE, self::PENDING, self::ERROR];

    private string $value;

    public function __construct(string $status)
    {
        $status = strtolower(trim($status));

        if (!in_array($status, self::ALLOWED, true)) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a valid site status. Allowed: %s',
                $status,
                implode(', ', self::ALLOWED)
            ));
        }

        $this->value = $status;
    }

    public static function active(): self   { return new self(self::ACTIVE); }
    public static function inactive(): self { return new self(self::INACTIVE); }
    public static function pending(): self  { return new self(self::PENDING); }
    public static function error(): self    { return new self(self::ERROR); }

    public function isActive(): bool   { return $this->value === self::ACTIVE; }
    public function isInactive(): bool { return $this->value === self::INACTIVE; }
    public function isPending(): bool  { return $this->value === self::PENDING; }
    public function isError(): bool    { return $this->value === self::ERROR; }

    public function getValue(): string  { return $this->value; }

    public function equals(self $other): bool { return $this->value === $other->value; }

    public function __toString(): string { return $this->value; }

    /** @return string[] */
    public static function allowedValues(): array { return self::ALLOWED; }
}
