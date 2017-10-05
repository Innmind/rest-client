<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Types,
    Definition\Type,
    Exception\InvalidArgumentException,
    Exception\NormalizationException,
    Exception\DenormalizationException
};

final class IntType implements Type
{
    public static function fromString(string $type, Types $types): Type
    {
        if ($type !== 'int') {
            throw new InvalidArgumentException;
        }

        return new self;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data)
    {
        try {
            return (int) $data;
        } catch (\Throwable $e) {
            throw new NormalizationException('The value must be an integer');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data)
    {
        try {
            return (int) $data;
        } catch (\Throwable $e) {
            throw new DenormalizationException('The value must be an integer');
        }
    }

    public function __toString(): string
    {
        return 'int';
    }
}
