<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Format;

use Innmind\Rest\Client\Exception\DomainException;
use Innmind\Immutable\Set;

final class Format
{
    private string $name;
    private Set $types;
    private int $priority;
    private MediaType $preferredType;

    public function __construct(string $name, Set $types, int $priority)
    {
        if ((string) $types->type() !== MediaType::class) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type Set<%s>',
                MediaType::class
            ));
        }

        if ($types->size() === 0) {
            throw new DomainException;
        }

        $this->name = $name;
        $this->types = $types;
        $this->priority = $priority;
        $this->preferredType = $types
            ->sort(function(MediaType $a, MediaType $b): bool {
                return $a->priority() < $b->priority();
            })
            ->first();
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Set<MediaType>
     */
    public function mediaTypes(): Set
    {
        return $this->types;
    }

    public function preferredMediaType(): MediaType
    {
        return $this->preferredType;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function toString(): string
    {
        return $this->name;
    }
}
