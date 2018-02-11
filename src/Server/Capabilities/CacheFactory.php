<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\Server\Capabilities as CapabilitiesInterface;
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\Adapter;
use Symfony\Component\Serializer\SerializerInterface;

final class CacheFactory implements Factory
{
    private $filesystem;
    private $serializer;
    private $factory;

    public function __construct(
        Adapter $filesystem,
        SerializerInterface $serializer,
        Factory $factory
    ) {
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
        $this->factory = $factory;
    }

    public function make(UrlInterface $url): CapabilitiesInterface
    {
        return new CacheCapabilities(
            $this->factory->make($url),
            $this->filesystem,
            $this->serializer,
            $url
        );
    }
}
