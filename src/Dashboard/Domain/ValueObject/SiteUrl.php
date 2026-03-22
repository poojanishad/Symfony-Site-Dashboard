<?php

declare(strict_types=1);

namespace App\Dashboard\Domain\ValueObject;

final class SiteUrl
{
    private string $value;

    public function __construct(string $url)
    {
        $url = trim($url);

        if (empty($url)) {
            throw new \InvalidArgumentException('Site URL cannot be empty.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid URL.', $url));
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Site URL must use http or https scheme.');
        }

        $this->value = $url;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
