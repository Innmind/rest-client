<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities as CapabilitiesInterface,
    Definition\HttpResource
};
use Innmind\Filesystem\{
    Adapter,
    File,
    Directory,
    Stream\StringStream,
    Exception\FileNotFound
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    SetInterface,
    Set,
    MapInterface,
    Map
};
use Symfony\Component\Serializer\SerializerInterface;

final class CacheCapabilities implements CapabilitiesInterface
{
    private $capabilities;
    private $filesystem;
    private $serializer;
    private $directory;
    private $names;
    private $definitions;

    public function __construct(
        CapabilitiesInterface $capabilities,
        Adapter $filesystem,
        SerializerInterface $serializer,
        UrlInterface $host
    ) {
        $this->capabilities = $capabilities;
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
        $this->directory = md5((string) $host);
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
            return $this->names = $this->serializer->deserialize(
                (string) $file->content(),
                'capabilities_names',
                'json'
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
            $definition = $this->serializer->deserialize(
                (string) $file->content(),
                HttpResource::class,
                'json',
                ['name' => $name]
            );
            $this->definitions = $this->definitions->put(
                $name,
                $definition
            );

            return $definition;
        } catch (FileNotFound $e) {
            $definition = $this->capabilities->get($name);
            $this->persist($name, $definition);
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

    private function persist(string $name, $data): self
    {
        if ($this->filesystem->has($this->directory)) {
            $directory = $this->filesystem->get($this->directory);
        } else {
            $directory = new Directory\Directory($this->directory);
        }

        $directory = $directory->add(
            new File\File(
                $name.'.json',
                new StringStream(
                    $this->serializer->serialize(
                        $data,
                        'json'
                    )
                )
            )
        );
        $this->filesystem->add($directory);

        return $this;
    }
}
