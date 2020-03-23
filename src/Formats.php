<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Format\Format,
    Format\MediaType,
    Exception\InvalidArgumentException,
    Exception\DomainException,
};
use Innmind\Immutable\{
    Map,
    Set,
};
use Negotiation\Negotiator;

final class Formats
{
    private Map $formats;
    private Negotiator $negotiator;
    private ?Set $types = null;

    public function __construct(Map $formats)
    {
        if (
            (string) $formats->keyType() !== 'string' ||
            (string) $formats->valueType() !== Format::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 1 must be of type Map<string, %s>',
                Format::class
            ));
        }

        if ($formats->size() === 0) {
            throw new DomainException;
        }

        $this->formats = $formats;
        $this->negotiator = new Negotiator;
    }

    public static function of(Format $first, Format ...$formats): self
    {
        $map = Map::of('string', Format::class)
            ($first->name(), $first);

        foreach ($formats as $format) {
            $map = $map->put($format->name(), $format);
        }

        return new self($map);
    }

    public function get(string $name): Format
    {
        return $this->formats->get($name);
    }

    /**
     * @return Map<string, Format>
     */
    public function all(): Map
    {
        return $this->formats;
    }

    /**
     * @return Set<MediaType>
     */
    public function mediaTypes(): Set
    {
        if ($this->types === null) {
            $this->types = $this
                ->formats
                ->reduce(
                    Set::of(MediaType::class),
                    function(Set $types, string $name, Format $format): Set {
                        return $types->merge($format->mediaTypes());
                    }
                );
        }

        return $this->types;
    }

    public function fromMediaType(string $wished): Format
    {
        $formats = $this
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
            });

        if ($formats->empty()) {
            throw new InvalidArgumentException;
        }

        return $formats->first();
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
