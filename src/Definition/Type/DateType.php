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
use Innmind\Immutable\StringPrimitive as Str;

final class DateType implements TypeInterface
{
    const PATTERN = '~date<(?<format>.+)>~';

    private $format;

    public function __construct(string $format)
    {
        if (empty($format)) {
            throw new InvalidArgumentException;
        }

        $this->format = $format;
    }

    public static function fromString(string $type, Types $types): TypeInterface
    {
        $type = new Str($type);

        if (!$type->match(self::PATTERN)) {
            throw new InvalidArgumentException;
        }

        return new self(
            (string) $type
                ->getMatches(self::PATTERN)
                ->get('format')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data)
    {
        if (is_string($data)) {
            try {
                $data = new \DateTimeImmutable($data);
            } catch (\Exception $e) {
                throw new NormalizationException('The value must be a date');
            }
        }

        if (!$data instanceof \DateTimeInterface) {
            throw new NormalizationException(
                'The value must be an instance of \DateTimeInterface'
            );
        }

        return $data->format($this->format);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data)
    {
        if (!is_string($data)) {
            throw new DenormalizationException('The value must be a string');
        }

        try {
            $date = \DateTimeImmutable::createFromFormat(
                $this->format,
                $data
            );

            if (!$date instanceof \DateTimeImmutable) {
                throw new \Exception;
            }

            return $date;
        } catch (\Throwable $e) {
            throw new DenormalizationException('The value must be a valid date');
        }
    }

    public function __toString(): string
    {
        return 'date<'.$this->format.'>';
    }
}
