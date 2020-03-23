<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\Server\Capabilities as CapabilitiesInterface;
use Innmind\Url\Url;

interface Factory
{
    public function __invoke(Url $url): CapabilitiesInterface;
}
