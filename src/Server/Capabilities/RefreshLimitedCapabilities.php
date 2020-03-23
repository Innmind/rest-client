<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities as CapabilitiesInterface,
    Definition\HttpResource,
};
use Innmind\Immutable\{
    Set,
    Map,
};

final class RefreshLimitedCapabilities implements CapabilitiesInterface
{
    private CapabilitiesInterface $capabilities;
    private bool $isFresh = false;

    public function __construct(CapabilitiesInterface $capabilities)
    {
        $this->capabilities = $capabilities;
    }

    public function names(): Set
    {
        return $this->capabilities->names();
    }

    public function get(string $name): HttpResource
    {
        return $this->capabilities->get($name);
    }

    public function definitions(): Map
    {
        return $this->capabilities->definitions();
    }

    public function refresh(): void
    {
        if ($this->isFresh) {
            return;
        }

        $this->capabilities->refresh();
        $this->isFresh = true;
    }
}
