<?php

declare(strict_types=1);

namespace AIGrowthOS\Domain\ValueObject;

class Email implements ValueObject
{
    private string $value;

    public function __construct(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address: $email");
        }
        $this->value = $email;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->value === $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
