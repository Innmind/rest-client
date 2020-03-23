<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities as CapabilitiesInterface,
    Serializer\Decode,
    Serializer\Encode,
    Serializer\Denormalizer\DenormalizeCapabilitiesNames,
    Serializer\Denormalizer\DenormalizeDefinition,
    Serializer\Normalizer\NormalizeDefinition,
};
use Innmind\Url\Url;
use Innmind\Filesystem\Adapter;

final class CacheFactory implements Factory
{
    private Adapter $filesystem;
    private Decode $decode;
    private Encode $encode;
    private DenormalizeCapabilitiesNames $denormalizeNames;
    private DenormalizeDefinition $denormalizeDefinition;
    private NormalizeDefinition $normalizeDefinition;
    private Factory $make;

    public function __construct(
        Adapter $filesystem,
        Decode $decode,
        Encode $encode,
        DenormalizeCapabilitiesNames $denormalizeNames,
        DenormalizeDefinition $denormalizeDefinition,
        NormalizeDefinition $normalizeDefinition,
        Factory $make
    ) {
        $this->filesystem = $filesystem;
        $this->decode = $decode;
        $this->encode = $encode;
        $this->denormalizeNames = $denormalizeNames;
        $this->denormalizeDefinition = $denormalizeDefinition;
        $this->normalizeDefinition = $normalizeDefinition;
        $this->make = $make;
    }

    public function __invoke(Url $url): CapabilitiesInterface
    {
        return new CacheCapabilities(
            ($this->make)($url),
            $this->filesystem,
            $this->decode,
            $this->encode,
            $this->denormalizeNames,
            $this->denormalizeDefinition,
            $this->normalizeDefinition,
            $url,
        );
    }
}
