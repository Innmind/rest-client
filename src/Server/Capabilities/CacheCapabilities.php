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
    Name,
    Exception\FileNotFound,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Set,
    Map,
};
use function Innmind\Immutable\unwrap;

final class CacheCapabilities implements CapabilitiesInterface
{
    private CapabilitiesInterface $capabilities;
    private Adapter $filesystem;
    private Decode $decode;
    private Encode $encode;
    private DenormalizeCapabilitiesNames $denormalizeNames;
    private DenormalizeDefinition $denormalizeDefinition;
    private NormalizeDefinition $normalizeDefinition;
    private Name $directory;
    /** @var Set<string>|null */
    private ?Set $names = null;
    /** @var Map<string, HttpResource> */
    private Map $definitions;

    public function __construct(
        CapabilitiesInterface $capabilities,
        Adapter $filesystem,
        Decode $decode,
        Encode $encode,
        DenormalizeCapabilitiesNames $denormalizeNames,
        DenormalizeDefinition $denormalizeDefinition,
        NormalizeDefinition $normalizeDefinition,
        Url $host
    ) {
        $this->capabilities = $capabilities;
        $this->filesystem = $filesystem;
        $this->decode = $decode;
        $this->encode = $encode;
        $this->denormalizeNames = $denormalizeNames;
        $this->denormalizeDefinition = $denormalizeDefinition;
        $this->normalizeDefinition = $normalizeDefinition;
        $this->directory = new Name(\md5($host->toString()));
        /** @var Map<string, HttpResource> */
        $this->definitions = Map::of('string', HttpResource::class);
    }

    /**
     * {@inheritdoc}
     */
    public function names(): Set
    {
        if ($this->names instanceof Set) {
            return $this->names;
        }

        try {
            $file = $this->load('.names');
            /** @var list<string> */
            $names = ($this->decode)('json', $file->content());

            return $this->names = ($this->denormalizeNames)(
                $names
            );
        } catch (FileNotFound $e) {
            $this->names = $this->capabilities->names();
            $this->persist('.names', unwrap($this->names));

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
            /** @var array{metas: array<scalar, scalar|array>, properties: array<string, array{variants: list<string>, type: string, access: list<string>, optional: bool}>, linkable_to: list<array{resource_path: string, relationship: string, parameters: list<string>}>, url: string, identity: string, rangeable: bool} */
            $definition = ($this->decode)('json', $file->content());
            $definition = ($this->denormalizeDefinition)(
                $definition,
                $name
            );
        } catch (FileNotFound $e) {
            $definition = $this->capabilities->get($name);
            $this->persist(
                $name,
                ($this->normalizeDefinition)($definition)
            );
        }

        $this->definitions = $this->definitions->put(
            $name,
            $definition
        );

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function definitions(): Map
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
        $file = new Name($file.'.json');

        if (!$this->filesystem->contains($this->directory)) {
            throw new FileNotFound;
        }

        $directory = $this->filesystem->get($this->directory);

        if (!$directory instanceof Directory) {
            throw new FileNotFound;
        }

        if (!$directory->contains($file)) {
            throw new FileNotFound;
        }

        return $directory->get($file);
    }

    private function persist(string $name, array $data): self
    {
        if ($this->filesystem->contains($this->directory)) {
            /** @var Directory */
            $directory = $this->filesystem->get($this->directory);
        } else {
            $directory = new Directory\Directory($this->directory);
        }

        $directory = $directory->add(
            File\File::named(
                $name.'.json',
                ($this->encode)($data)
            )
        );
        $this->filesystem->add($directory);

        return $this;
    }
}
