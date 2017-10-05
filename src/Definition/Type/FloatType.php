<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Types,
    Definition\Type,
    Exception\DomainException,
    Exception\NormalizationException,
    Exception\DenormalizationException
};

final class FloatType implements Type
{
    public static function fromString(string $type, Types $types): Type
    {
        if ($type !== 'float') {
            throw new DomainException;
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
