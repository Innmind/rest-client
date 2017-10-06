<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\Server\Capabilities as CapabilitiesInterface;
use Innmind\Url\UrlInterface;

final class RefreshLimitedFactory implements Factory
{
    private $factory;

    public function __construct(Factory $factory)
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
