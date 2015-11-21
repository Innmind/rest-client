<?php

namespace Innmind\Rest\Client;

interface HttpResourceInterface
{
    /**
     * Set a property on the resource
     *
     * @param string $property
     * @param mixed $data
     *
     * @return Resource self
     */
    public function set($property, $data);

    /**
     * Check if property is set in this resource
     *
     * @param string $property
     *
     * @return bool
     */
    public function has($property);

    /**
     * Return the value for the given data
     *
     * @param string $property
     *
     * @return mixed
     */
    public function get($property);

    /**
     * Remove the given groperty
     *
     * @param string $property
     *
     * @return Resource self
     */
    public function remove($property);
}
