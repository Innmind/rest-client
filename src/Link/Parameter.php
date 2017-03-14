<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Link;

use Innmind\Rest\Client\Exception\InvalidArgumentException;

final class Parameter implements ParameterInterface
{
    private $key;
    private $value;

    public function __construct(string $key, string $value)
    {
        if (empty($key)) {
            throw new InvalidArgumentException;
        }

        $this->key = $key;
        $this->value = $value;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function value(): string
    {
        return $this->value;
    }
}
