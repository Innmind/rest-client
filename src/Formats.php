<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Format\Format,
    Format\MediaType,
    Exception\InvalidArgumentException,
    Exception\DomainException
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set
};
use Negotiation\Negotiator;

final class Formats
{
    private $formats;
    private $negotiator;
    private $types;

    public function __construct(MapInterface $formats)
    {
        if (
            (string) $formats->keyType() !== 'string' ||
            (string) $formats->valueType() !== Format::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 1 must be of type MapInterface<string, %s>',
                Format::class
            ));
        }

        if ($formats->size() === 0) {
            throw new DomainException;
        }

        $this->formats = $formats;
        $this->negotiator = new Negotiator;
    }


    public function get(string $name): Format
    {
        return $this->formats->get($name);
    }

    /**
     * @return MapInterface<string, Format>
     */
    public function all(): MapInterface
    {
        return $this->formats;
    }

    /**
     * @return SetInterface<MediaType>
     */
    public function mediaTypes(): SetInterface
    {
        if ($this->types === null) {
            $this->types = $this
                ->formats
                ->reduce(
                    new Set(MediaType::class),
                    function(Set $types, string $name, Format $format): Set {
                        return $types->merge($format->mediaTypes());
                    }
                );
        }

        return $this->types;
    }

    public function fromMediaType(string $wished): Format
    {
        $format = $this
            ->formats
            ->values()
            ->filter(function(Format $format) use ($wished) {
                return $format
                    ->mediaTypes()
                    ->reduce(
                        false,
                        function(bool $carry, MediaType $mediaType) use ($wished): bool {
                            if ($carry === true) {
                                return true;
                            }

                            return (string) $mediaType === $wished;
                        }
                    );
            })
            ->current();

        if (!$format instanceof Format) {
            throw new InvalidArgumentException;
        }

        return $format;
    }

    public function matching(string $wished): Format
    {
        $best = $this->negotiator->getBest(
            $wished,
            $this
                ->mediaTypes()
                ->reduce(
                    [],
                    function(array $carry, MediaType $type): array {
                        $carry[] = (string) $type;

                        return $carry;
                    }
                )
        );

        if ($best === null) {
            throw new InvalidArgumentException;
        }

        return $this->best($best->getBasePart().'/'.$best->getSubPart());
    }

    private function best(string $mediaType): Format
    {
        if ($mediaType === '*/*') {
            return $this
                ->formats
                ->values()
                ->sort(function(Format $a, Format $b): bool {
                    return $a->priority() > $b->priority();
                })
                ->first();
        }

        return $this->fromMediaType($mediaType);
    }
}
