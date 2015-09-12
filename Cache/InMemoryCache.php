<?php

namespace Innmind\Rest\Client\Cache;

use Innmind\Rest\Client\CacheInterface;
use Innmind\Rest\Client\Definition\Resource;

class InMemoryCache implements CacheInterface
{
    protected $resources = [];

    /**
     * {@inheritdoc}
     */
    public function save($key, Resource $resource)
    {
        $this->resources[(string) $key] = $resource;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return array_key_exists((string) $key, $this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return array_keys($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(sprintf(
                'No resource definition found for %s',
                $key
            ));
        }

        return $this->resources[(string) $key];
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->resources[(string) $key]);

        return $this;
    }
}
