<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\HttpResource;

use Innmind\Rest\Client\Exception\InvalidArgumentException;

final class Property
{
    private $name;
    private $value;

    public function __construct(string $name, $value)
    {
        if (empty($name)) {
            throw new InvalidArgumentException;
        }

        $this->name = $name;
        $this->value = $value;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value()
    {
        return $this->value;
    }
}
