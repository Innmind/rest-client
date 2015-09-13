<?php

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\Definition\Resource;

/**
 * Describe the interface to store resource definitions
 */
interface CacheInterface
{
    /**
     * Save a resource definition under the specified key
     *
     * @param string $key
     * @param Definition\Resource $resource
     *
     * @return CacheInterface self
     */
    public function save($key, Resource $resource);

    /**
     * Check if the named resource exist
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key);

    /**
     * Return all the resource keys that are cached
     *
     * @return array
     */
    public function keys();

    /**
     * Retrieve the resource definition saved under the given key
     *
     * @param string $key
     *
     * @return Definition\Resource
     */
    public function get($key);

    /**
     * Remove the definition from the storage
     *
     * @param string $key
     *
     * @return CacheInterface self
     */
    public function remove($key);
}
