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
use Innmind\Immutable\{
    Str,
    Map,
};

final class MapType implements Type
{
    private const PATTERN = '~map<(?<key>.+), ?(?<value>.+)>~';

    private Type $key;
    private Type $value;
    private Map $denormalized;

    public function __construct(Type $key, Type $value)
    {
        $this->key = $key;
        $this->value = $value;
        $this->denormalized = Map::of(
            $key instanceof DateType ?
                \DateTimeImmutable::class : $key->toString(),
            $value instanceof DateType ?
                \DateTimeImmutable::class : $value->toString(),
        );
    }

    public static function of(string $type, Types $build): Type
    {
        $type = Str::of($type);

        if (!$type->matches(self::PATTERN)) {
            throw new DomainException($type->toString());
        }

        $matches = $type->capture(self::PATTERN);

        return new self(
            $build($matches->get('key')->toString()),
            $build($matches->get('value')->toString()),
        );
    }

    public function normalize($data)
    {
        if (!$data instanceof Map) {
            throw new NormalizationException(
                'The value must be an instance of Innmind\Immutable\Map',
            );
        }

        return $data->reduce(
            [],
            function(array $values, $key, $value): array {
                /** @psalm-suppress MixedAssignment */
                $key = $this->key->normalize($key);
                /**
                 * @psalm-suppress MixedAssignment
                 * @psalm-suppress MixedArrayOffset
                 */
                $values[$key] = $this->value->normalize($value);

                return $values;
            }
        );
    }

    public function denormalize($data)
    {
        if (!\is_array($data) && !$data instanceof \Traversable) {
            throw new DenormalizationException('The value must be an array');
        }

        $map = $this->denormalized;

        try {
            /** @psalm-suppress MixedAssignment */
            foreach ($data as $key => $value) {
                $map = ($map)(
                    $this->key->denormalize($key),
                    $this->value->denormalize($value),
                );
            }

            return $map;
        } catch (\Throwable $e) {
            throw new DenormalizationException('The value must be a valid map');
        }
    }

    public function toString(): string
    {
        return 'map<'.$this->key->toString().', '.$this->value->toString().'>';
    }
}
