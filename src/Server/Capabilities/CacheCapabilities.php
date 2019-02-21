<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities as CapabilitiesInterface,
    Definition\HttpResource,
    Serializer\Decode,
    Serializer\Encode,
    Serializer\Denormalizer\DenormalizeCapabilitiesNames,
    Serializer\Denormalizer\DenormalizeDefinition,
    Serializer\Normalizer\NormalizeDefinition,
};
use Innmind\Filesystem\{
    Adapter,
    File,
    Directory,
    Exception\FileNotFound,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
    Map,
};

final class CacheCapabilities implements CapabilitiesInterface
{
    private $capabilities;
    private $filesystem;
    private $decode;
    private $encode;
    private $denormalizeNames;
    private $denormalizeDefinition;
    private $normalizeDefinition;
    private $directory;
    private $names;
    private $definitions;

    public function __construct(
        CapabilitiesInterface $capabilities,
        Adapter $filesystem,
        Decode $decode,
        Encode $encode,
        DenormalizeCapabilitiesNames $denormalizeNames,
        DenormalizeDefinition $denormalizeDefinition,
        NormalizeDefinition $normalizeDefinition,
        UrlInterface $host
    ) {
        $this->capabilities = $capabilities;
        $this->filesystem = $filesystem;
        $this->decode = $decode;
        $this->encode = $encode;
        $this->denormalizeNames = $denormalizeNames;
        $this->denormalizeDefinition = $denormalizeDefinition;
        $this->normalizeDefinition = $normalizeDefinition;
        $this->directory = \md5((string) $host);
        $this->definitions = new Map('string', HttpResource::class);
    }

    /**
     * {@inheritdoc}
     */
    public function names(): SetInterface
    {
        if ($this->names instanceof SetInterface) {
            return $this->names;
        }

        try {
            $file = $this->load('.names');
            return $this->names = ($this->denormalizeNames)(
                ($this->decode)('json', $file->content())
            );
        } catch (FileNotFound $e) {
            $this->names = $this->capabilities->names();
            $this->persist('.names', $this->names->toPrimitive());

            return $this->names;
        }
    }

    public function get(string $name): HttpResource
    {
        if ($this->definitions->contains($name)) {
            return $this->definitions->get($name);
        }

        try {
            $file = $this->load($name);
            $definition = ($this->denormalizeDefinition)(
                ($this->decode)('json', $file->content()),
                $name
            );
            $this->definitions = $this->definitions->put(
                $name,
                $definition
            );

            return $definition;
        } catch (FileNotFound $e) {
            $definition = $this->capabilities->get($name);
            $this->persist(
                $name,
                ($this->normalizeDefinition)($definition)
            );
            $this->definitions = $this->definitions->put(
                $name,
                $definition
            );

            return $definition;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function definitions(): MapInterface
    {
        $this->names()->foreach(function(string $name) {
            $this->get($name);
        });

        return $this->definitions;
    }

    /**
     * {@inheritdoc}
     */
    public function refresh(): CapabilitiesInterface
    {
        $this->names = null;
        $this->definitions = $this->definitions->clear();
        $this->filesystem->remove($this->directory);
        $this->capabilities->refresh();

        return $this;
    }

    private function load(string $file): File
    {
        $file .= '.json';

        if (!$this->filesystem->has($this->directory)) {
            throw new FileNotFound;
        }

        $directory = $this->filesystem->get($this->directory);

        if (!$directory instanceof Directory) {
            throw new FileNotFound;
        }

        if (!$directory->has($file)) {
            throw new FileNotFound;
        }

        return $directory->get($file);
    }

    private function persist(string $name, array $data): self
    {
        if ($this->filesystem->has($this->directory)) {
            $directory = $this->filesystem->get($this->directory);
        } else {
            $directory = new Directory\Directory($this->directory);
        }

        $directory = $directory->add(
            new File\File(
                $name.'.json',
                ($this->encode)($data)
            )
        );
        $this->filesystem->add($directory);

        return $this;
    }
}
