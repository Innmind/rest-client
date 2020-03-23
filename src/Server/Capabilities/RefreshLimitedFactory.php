<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\Server\Capabilities as CapabilitiesInterface;
use Innmind\Url\UrlInterface;

final class RefreshLimitedFactory implements Factory
{
    private Factory $make;

    public function __construct(Factory $make)
    {
        $this->make = $make;
    }

    public function __invoke(UrlInterface $url): CapabilitiesInterface
    {
        return new RefreshLimitedCapabilities(
            ($this->make)($url)
        );
    }
}
