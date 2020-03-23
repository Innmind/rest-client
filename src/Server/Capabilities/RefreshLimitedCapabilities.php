<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities as CapabilitiesInterface,
    Definition\HttpResource,
};
use Innmind\Immutable\{
    SetInterface,
    MapInterface,
};

final class RefreshLimitedCapabilities implements CapabilitiesInterface
{
    private CapabilitiesInterface $capabilities;
    private bool $isFresh = false;

    public function __construct(CapabilitiesInterface $capabilities)
    {
        $this->capabilities = $capabilities;
    }

    /**
     * {@inheritdoc}
     */
    public function names(): SetInterface
    {
        return $this->capabilities->names();
    }

    public function get(string $name): HttpResource
    {
        return $this->capabilities->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function definitions(): MapInterface
    {
        return $this->capabilities->definitions();
    }

    /**
     * {@inheritdoc}
     */
    public function refresh(): CapabilitiesInterface
    {
        if ($this->isFresh) {
            return $this;
        }

        $this->capabilities->refresh();
        $this->isFresh = true;

        return $this;
    }
}
