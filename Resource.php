<?php

namespace Innmind\Rest\Client;

class Resource
{
    protected $data = [];

    /**
     * Set a property on the resource
     *
     * @param string $property
     * @param mixed $data
     *
     * @return Resource self
     */
    public function set($property, $data)
    {
        $this->data[(string) $property] = $data;

        return $this;
    }

    /**
     * Check if property is set in this resource
     *
     * @param string $property
     *
     * @return bool
     */
    public function has($property)
    {
        return array_key_exists((string) $property, $this->data);
    }

    /**
     * Return the value for the given data
     *
     * @param string $property
     *
     * @return mixed
     */
    public function get($property)
    {
        if (!$this->has($property)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown property "%s"',
                $property
            ));
        }

        return $this->data[(string) $property];
    }

    /**
     * Remove the given groperty
     *
     * @param string $property
     *
     * @return Resource self
     */
    public function remove($property)
    {
        if ($this->has($property)) {
            unset($this->data[(string) $property]);
        }

        return $this;
    }
}
