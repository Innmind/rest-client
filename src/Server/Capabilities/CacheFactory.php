<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\Server\{
    CapabilitiesInterface,
    CacheCapabilities
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\Adapter;
use SYmfony\Component\Serializer\SerializerInterface;

final class CacheFactory implements FactoryInterface
{
    private $filesystem;
    private $serializer;
    private $factory;

    public function __construct(
        Adapter $filesystem,
        SerializerInterface $serializer,
        FactoryInterface $factory
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
