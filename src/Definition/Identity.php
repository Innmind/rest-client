<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Exception\DomainException;

final class Identity
{
    private $name;

    public function __construct(string $name)
    {
        if (empty($name)) {
            throw new DomainException;
        }

        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
