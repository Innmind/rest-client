<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Types,
    Definition\TypeInterface,
    Exception\InvalidArgumentException
};

final class BoolType implements TypeInterface
{
    public static function fromString(string $type, Types $types): TypeInterface
    {
        if ($type !== 'bool') {
            throw new InvalidArgumentException;
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
