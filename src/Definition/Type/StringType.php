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

final class StringType implements Type
{
    public static function of(string $type, Types $build): Type
    {
        if ($type !== 'string') {
            throw new DomainException;
        }

        return new self;
    }

    public function normalize($data)
    {
        try {
            return (string) $data;
        } catch (\Throwable $e) {
            throw new NormalizationException('The value must be a string');
        }
    }

    public function denormalize($data)
    {
        try {
            return (string) $data;
        } catch (\Throwable $e) {
            throw new DenormalizationException('The value must be a string');
        }
    }

    public function toString(): string
    {
        return 'string';
    }
}
