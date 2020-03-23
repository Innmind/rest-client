<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Types,
    Definition\Type,
    Exception\DomainException,
    Exception\NormalizationException,
    Exception\DenormalizationException,
};

final class FloatType implements Type
{
    public static function of(string $type, Types $build): Type
    {
        if ($type !== 'float') {
            throw new DomainException($type);
        }

        return new self;
    }

    public function normalize($data)
    {
        try {
            return (float) $data;
        } catch (\Throwable $e) {
            throw new NormalizationException('The value must be a float');
        }
    }

    public function denormalize($data)
    {
        try {
            return (float) $data;
        } catch (\Throwable $e) {
            throw new DenormalizationException('The value must be a float');
        }
    }

    public function toString(): string
    {
        return 'float';
    }
}
