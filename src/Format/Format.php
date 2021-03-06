<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Format;

use Innmind\Rest\Client\Exception\DomainException;
use Innmind\Immutable\Set;
use function Innmind\Immutable\assertSet;

final class Format
{
    private string $name;
    /** @var Set<MediaType> */
    private Set $types;
    private int $priority;
    private MediaType $preferredType;

    /**
     * @param Set<MediaType> $types
     */
    public function __construct(string $name, Set $types, int $priority)
    {
        assertSet(MediaType::class, $types, 2);

        if ($types->empty()) {
            throw new DomainException;
        }

        $this->name = $name;
        $this->types = $types;
        $this->priority = $priority;
        $this->preferredType = $types
            ->sort(static function(MediaType $a, MediaType $b): int {
                return (int) ($a->priority() < $b->priority());
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
