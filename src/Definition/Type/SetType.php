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
use Innmind\Immutable\{
    Str,
    Set,
    SetInterface
};

final class SetType implements Type
{
    const PATTERN = '~set<(?<inner>.+)>~';

    private $inner;
    private $denormalized;

    public function __construct(Type $inner)
    {
        $this->inner = $inner;
        $this->denormalized = new Set(
            $inner instanceof DateType ?
                \DateTimeImmutable::class : (string) $inner
        );
    }

    public static function fromString(string $type, Types $types): Type
    {
        $type = new Str($type);

        if (!$type->matches(self::PATTERN)) {
            throw new InvalidArgumentException;
        }

        return new self(
            $types->build(
                (string) $type
                    ->capture(self::PATTERN)
                    ->get('inner')
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data)
    {
        if (!$data instanceof SetInterface) {
            throw new NormalizationException(
                'The value must be an instance of Innmind\Immutable\SetInterface'
            );
        }

        return $data->reduce(
            [],
            function(array $values, $value): array {
                $values[] = $this->inner->normalize($value);

                return $values;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data)
    {
        if (!is_array($data)) {
            throw new DenormalizationException('The value must be an array');
        }

        $set = $this->denormalized;

        try {
            foreach ($data as $value) {
                $set = $set->add(
                    $this->inner->denormalize($value)
                );
            }

            return $set;
        } catch (\Throwable $e) {
            throw new DenormalizationException('The value must be a valid set');
        }
    }

    public function __toString(): string
    {
        return 'set<'.$this->inner.'>';
    }
}
