<?php

namespace Innmind\Rest\Client\Server;

/**
 * Minimum implementation needed for a collection of resources
 */
interface CollectionInterface extends \Iterator, \Countable
{
    /**
     * Return the definition that represent each resource of the collection
     *
     * @return Innmind\Rest\Client\Definition\Resource
     */
    public function getDefinition();
}
