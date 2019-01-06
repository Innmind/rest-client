<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Identity;

use Innmind\Rest\Client\{
    Identity as IdentityInterface,
    Exception\DomainException,
};

class Identity implements IdentityInterface
{
    private $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
