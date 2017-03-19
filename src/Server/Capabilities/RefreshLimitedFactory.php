<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\Server\{
    CapabilitiesInterface,
    RefreshLimitedCapabilities
};
use Innmind\Url\UrlInterface;

final class RefreshLimitedFactory implements FactoryInterface
{
    private $factory;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function make(UrlInterface $url): CapabilitiesInterface
    {
        return new RefreshLimitedCapabilities(
            $this->factory->make($url)
        );
    }
}
