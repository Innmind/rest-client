<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Types,
    Definition\Type,
    Exception\DomainException
};

final class BoolType implements Type
{
    public static function fromString(string $type, Types $types): Type
    {
        if ($type !== 'bool') {
            throw new DomainException;
        }

        return new self;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data)
    {
        return (bool) $data;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data)
    {
        return (bool) $data;
    }

    public function __toString(): string
    {
        return 'bool';
    }
}
