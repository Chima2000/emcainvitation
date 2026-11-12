<?php

namespace App\Exceptions;

use RuntimeException;

class SmsNotSent extends RuntimeException
{
    public function __construct(string $message, public readonly bool $isWorthRetrying = false)
    {
        parent::__construct($message);
    }

    public static function temporarily(string $message): self
    {
        return new self($message, isWorthRetrying: true);
    }

    public static function permanently(string $message): self
    {
        return new self($message, isWorthRetrying: false);
    }
}
