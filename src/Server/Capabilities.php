<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\Definition\HttpResource;
use Innmind\Immutable\{
    Map,
    Set,
};

interface Capabilities
{
    /**
     * @return Set<string>
     */
    public function names(): Set;
    public function get(string $name): HttpResource;

    /**
     * @return Map<string, HttpResource>
     */
    public function definitions(): Map;

    /**
     * Clear all definition references it holds, in order to be sure next time
     * we access one it is a fresh definition
     */
    public function refresh(): void;
}
