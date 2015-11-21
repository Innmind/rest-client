<?php

namespace Innmind\Rest\Client;

class HttpResource implements HttpResourceInterface
{
    protected $data = [];

    /**
     * {@inheritdoc}
     */
    public function set($property, $data)
    {
        $this->data[(string) $property] = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has($property)
    {
        return array_key_exists((string) $property, $this->data);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function remove($property)
    {
        if ($this->has($property)) {
            unset($this->data[(string) $property]);
        }

        return $this;
    }
}
