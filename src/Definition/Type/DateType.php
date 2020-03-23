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
use Innmind\Immutable\Str;

final class DateType implements Type
{
    private const PATTERN = '~date<(?<format>.+)>~';

    private string $format;

    public function __construct(string $format)
    {
        if (Str::of($format)->empty()) {
            throw new DomainException;
        }

        $this->format = $format;
    }

    public static function of(string $type, Types $build): Type
    {
        $type = Str::of($type);

        if (!$type->matches(self::PATTERN)) {
            throw new DomainException;
        }

        return new self(
            $type
                ->capture(self::PATTERN)
                ->get('format')
                ->toString(),
        );
    }

    public function normalize($data)
    {
        if (\is_string($data)) {
            try {
                $data = new \DateTimeImmutable($data);
            } catch (\Exception $e) {
                throw new NormalizationException('The value must be a date');
            }
        }

        if (!$data instanceof \DateTimeInterface) {
            throw new NormalizationException(
                'The value must be an instance of \DateTimeInterface',
            );
        }

        return $data->format($this->format);
    }

    public function denormalize($data)
    {
        if (!\is_string($data)) {
            throw new DenormalizationException('The value must be a string');
        }

        try {
            $date = \DateTimeImmutable::createFromFormat(
                $this->format,
                $data,
            );

            if (!$date instanceof \DateTimeImmutable) {
                throw new \Exception;
            }

            return $date;
        } catch (\Throwable $e) {
            throw new DenormalizationException('The value must be a valid date');
        }
    }

    public function toString(): string
    {
        return 'date<'.$this->format.'>';
    }
}
