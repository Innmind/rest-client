<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Link;
use Innmind\Immutable\Set;
use function Innmind\Immutable\assertSet;

final class AllowedLink
{
    private string $resourcePath;
    private string $relationship;
    /** @var Set<string> */
    private Set $parameters;

    /**
     * @param Set<string> $parameters
     */
    public function __construct(
        string $resourcePath,
        string $relationship,
        Set $parameters
    ) {
        assertSet('string', $parameters, 3);

        $this->resourcePath = $resourcePath;
        $this->relationship = $relationship;
        $this->parameters = $parameters;
    }

    public function resourcePath(): string
    {
        return $this->resourcePath;
    }

    public function relationship(): string
    {
        return $this->relationship;
    }

    /**
     * @return Set<string>
     */
    public function parameters(): Set
    {
        return $this->parameters;
    }

    public function allows(Link $link): bool
    {
        if ($link->definition() !== $this->resourcePath) {
            return false;
        }

        if ($link->relationship() !== $this->relationship) {
            return false;
        }

        return $this->parameters->reduce(
            true,
            static function(bool $accept, string $parameter) use ($link): bool {
                return $accept && $link->parameters()->contains($parameter);
            },
        );
    }
}
