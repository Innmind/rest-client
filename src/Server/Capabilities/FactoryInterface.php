<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\Server\CapabilitiesInterface;
use Innmind\Url\UrlInterface;

interface FactoryInterface
{
    public function make(UrlInterface $url): CapabilitiesInterface;
}
