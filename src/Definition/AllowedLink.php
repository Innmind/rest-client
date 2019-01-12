<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Link;
use Innmind\Immutable\SetInterface;

final class AllowedLink
{
    private $resourcePath;
    private $relationship;
    private $parameters;

    public function __construct(
        string $resourcePath,
        string $relationship,
        SetInterface $parameters
    ) {
        if ((string) $parameters->type() !== 'string') {
            throw new \TypeError('Argument 3 must be of type SetInterface<string>');
        }

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
     * @return SetInterface<string>
     */
    public function parameters(): SetInterface
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
            }
        );
    }
}
