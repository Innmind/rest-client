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
    MapInterface,
    Map,
};

final class MapType implements Type
{
    const PATTERN = '~map<(?<key>.+), ?(?<value>.+)>~';

    private $key;
    private $value;
    private $denormalized;

    public function __construct(Type $key, Type $value)
    {
        $this->key = $key;
        $this->value = $value;
        $this->denormalized = new Map(
            $key instanceof DateType ?
                \DateTimeImmutable::class : (string) $key,
            $value instanceof DateType ?
                \DateTimeImmutable::class : (string) $value
        );
    }

    public static function fromString(string $type, Types $build): Type
    {
        $type = new Str($type);

        if (!$type->matches(self::PATTERN)) {
            throw new DomainException;
        }

        $matches = $type->capture(self::PATTERN);

        return new self(
            $build((string) $matches->get('key')),
            $build((string) $matches->get('value'))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data)
    {
        if (!$data instanceof MapInterface) {
            throw new NormalizationException(
                'The value must be an instance of Innmind\Immutable\MapInterface'
            );
        }

        return $data->reduce(
            [],
            function(array $values, $key, $value): array {
                $key = $this->key->normalize($key);
                $values[$key] = $this->value->normalize($value);

                return $values;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data)
    {
        if (!\is_array($data) && !$data instanceof \Traversable) {
            throw new DenormalizationException('The value must be an array');
        }

        $map = $this->denormalized;

        try {
            foreach ($data as $key => $value) {
                $map = $map->put(
                    $this->key->denormalize($key),
                    $this->value->denormalize($value)
                );
            }

            return $map;
        } catch (\Throwable $e) {
            throw new DenormalizationException('The value must be a valid map');
        }
    }

    public function __toString(): string
    {
        return 'map<'.$this->key.', '.$this->value.'>';
    }
}
