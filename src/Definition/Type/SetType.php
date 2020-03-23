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
    Set,
};

final class SetType implements Type
{
    private const PATTERN = '~set<(?<inner>.+)>~';

    private Type $inner;
    private Set $denormalized;

    public function __construct(Type $inner)
    {
        $this->inner = $inner;
        $this->denormalized = Set::of(
            $inner instanceof DateType ?
                \DateTimeImmutable::class : $inner->toString(),
        );
    }

    public static function of(string $type, Types $build): Type
    {
        $type = Str::of($type);

        if (!$type->matches(self::PATTERN)) {
            throw new DomainException;
        }

        return new self(
            $build(
                $type
                    ->capture(self::PATTERN)
                    ->get('inner')
                    ->toString(),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data)
    {
        if (!$data instanceof Set) {
            throw new NormalizationException(
                'The value must be an instance of Innmind\Immutable\Set',
            );
        }

        return $data->reduce(
            [],
            function(array $values, $value): array {
                /** @psalm-suppress MixedAssignment */
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
        if (!\is_array($data)) {
            throw new DenormalizationException('The value must be an array');
        }

        $set = $this->denormalized;

        try {
            /** @psalm-suppress MixedAssignment */
            foreach ($data as $value) {
                $set = ($set)($this->inner->denormalize($value));
            }

            return $set;
        } catch (\Throwable $e) {
            throw new DenormalizationException('The value must be a valid set');
        }
    }

    public function toString(): string
    {
        return 'set<'.$this->inner->toString().'>';
    }
}
