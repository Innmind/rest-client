<?php

namespace Innmind\Rest\Client\Server;

interface HttpResourceInterface
{
    /**
     * Return the resource definition
     *
     * @return Innmind\Rest\Client\Definition\ResourceDefinition
     */
    public function getDefinition();

    /**
     * Return the value of a property
     *
     * @param string $property
     *
     * @return mixed
     */
    public function get($property);

    /**
     * Check the resource has the given property
     *
     * @param string $property
     *
     * @return bool
     */
    public function has($property);
}
