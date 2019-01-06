<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities as CapabilitiesInterface,
    Serializer\Decode,
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\Adapter;
use Symfony\Component\Serializer\SerializerInterface;

final class CacheFactory implements Factory
{
    private $filesystem;
    private $decode;
    private $serializer;
    private $make;

    public function __construct(
        Adapter $filesystem,
        Decode $decode,
        SerializerInterface $serializer,
        Factory $make
    ) {
        $this->filesystem = $filesystem;
        $this->decode = $decode;
        $this->serializer = $serializer;
        $this->make = $make;
    }

    public function __invoke(UrlInterface $url): CapabilitiesInterface
    {
        return new CacheCapabilities(
            ($this->make)($url),
            $this->filesystem,
            $this->decode,
            $this->serializer,
            $url
        );
    }
}
