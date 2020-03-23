<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Types,
    Definition\Type,
    Exception\DomainException,
};

final class BoolType implements Type
{
    public static function of(string $type, Types $build): Type
    {
        if ($type !== 'bool') {
            throw new DomainException;
        }

        return new self;
    }

    public function normalize($data)
    {
        return (bool) $data;
    }

    public function denormalize($data)
    {
        return (bool) $data;
    }

    public function toString(): string
    {
        return 'bool';
    }
}
