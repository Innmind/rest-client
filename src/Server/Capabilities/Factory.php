<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\Server\Capabilities as CapabilitiesInterface;
use Innmind\Url\UrlInterface;

interface Factory
{
    public function __invoke(UrlInterface $url): CapabilitiesInterface;
}
