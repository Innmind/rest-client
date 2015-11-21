<?php

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\Definition\ResourceDefinition as Definition;

/**
 * Describe the interface to store resource definitions
 */
interface CacheInterface
{
    /**
     * Save a resource definition under the specified key
     *
     * @param string $key
     * @param Definition $resource
     *
     * @return CacheInterface self
     */
    public function save($key, Definition $resource);

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
     * @return Definition
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
