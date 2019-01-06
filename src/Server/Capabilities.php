<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\Definition\HttpResource;
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
};

interface Capabilities
{
    /**
     * @return SetInterface<string>
     */
    public function names(): SetInterface;
    public function get(string $name): HttpResource;

    /**
     * @return MapInterface<string, HttpResource>
     */
    public function definitions(): MapInterface;

    /**
     * Clear all definition references it holds, in order to be sure next time
     * we access one it is a fresh definition
     */
    public function refresh(): self;
}
