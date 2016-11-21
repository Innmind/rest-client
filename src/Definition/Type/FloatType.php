<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Types,
    Definition\TypeInterface,
    Exception\InvalidArgumentException,
    Exception\NormalizationException,
    Exception\DenormalizationException
};

final class FloatType implements TypeInterface
{
    public static function fromString(string $type, Types $types): TypeInterface
    {
        if ($type !== 'float') {
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
            return (float) $data;
        } catch (\Throwable $e) {
            throw new NormalizationException('The value must be a float');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data)
    {
        try {
            return (float) $data;
        } catch (\Throwable $e) {
            throw new DenormalizationException('The value must be a float');
        }
    }

    public function __toString(): string
    {
        return 'float';
    }
}
