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

final class StringType implements TypeInterface
{
    public static function fromString(string $type, Types $types): TypeInterface
    {
        if ($type !== 'string') {
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
            return (string) $data;
        } catch (\Throwable $e) {
            throw new NormalizationException('The value must be a string');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data)
    {
        try {
            return (string) $data;
        } catch (\Throwable $e) {
            throw new DenormalizationException('The value must be a string');
        }
    }

    public function __toString(): string
    {
        return 'string';
    }
}
