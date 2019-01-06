<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities as CapabilitiesInterface,
    Serializer\Decode,
    Serializer\Denormalizer\DenormalizeCapabilitiesNames,
    Serializer\Denormalizer\DenormalizeDefinition,
    Serializer\Normalizer\NormalizeDefinition,
};
use Innmind\Url\UrlInterface;
use Innmind\Filesystem\Adapter;
use Symfony\Component\Serializer\SerializerInterface;

final class CacheFactory implements Factory
{
    private $filesystem;
    private $decode;
    private $denormalizeNames;
    private $denormalizeDefinition;
    private $normalizeDefinition;
    private $serializer;
    private $make;

    public function __construct(
        Adapter $filesystem,
        Decode $decode,
        DenormalizeCapabilitiesNames $denormalizeNames,
        DenormalizeDefinition $denormalizeDefinition,
        NormalizeDefinition $normalizeDefinition,
        SerializerInterface $serializer,
        Factory $make
    ) {
        $this->filesystem = $filesystem;
        $this->decode = $decode;
        $this->denormalizeNames = $denormalizeNames;
        $this->denormalizeDefinition = $denormalizeDefinition;
        $this->normalizeDefinition = $normalizeDefinition;
        $this->serializer = $serializer;
        $this->make = $make;
    }

    public function __invoke(UrlInterface $url): CapabilitiesInterface
    {
        return new CacheCapabilities(
            ($this->make)($url),
            $this->filesystem,
            $this->decode,
            $this->denormalizeNames,
            $this->denormalizeDefinition,
            $this->normalizeDefinition,
            $this->serializer,
            $url
        );
    }
}
