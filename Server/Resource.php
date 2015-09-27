<?php

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\Definition\Resource as Definition;
use Innmind\Rest\Client\Client;

class Resource
{
    protected $definition;
    protected $properties;
    protected $subResources;
    protected $client;

    public function __construct(
        Definition $definition,
        array $properties,
        array $subResources,
        Client $client
    ) {
        $this->definition = $definition;
        $this->properties = $properties;
        $this->subResources = $subResources;
        $this->client = $client;
    }

    /**
     * Return the resource definition
     *
     * @return Definition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Return the value of a property
     *
     * @param string $property
     *
     * @return mixed
     */
    public function get($property)
    {
        $property = (string) $property;

        if (isset($this->properties[$property])) {
            return $this->properties[$property];
        } else if (isset($this->subResources[$property])) {
            if (is_array($this->subResources[$property])) {
                $collection = new Collection(
                    $this->definition->getProperty($property)->getResource(),
                    $this->subResources[$property],
                    null,
                    $this->client
                );
                $this->properties[$property] = $collection;
                unset($this->subResources[$property]);

                return $collection;
            } else {
                $resource = $this->client->read($this->subResources[$property]);
                $this->properties[$property] = $resource;
                unset($this->subResources[$property]);

                return $resource;
            }
        }

        foreach ($this->definition->getProperties() as $prop) {
            if ($prop->isVariantOf($property)) {
                return $this->get((string) $prop);
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Property "%s" not found',
            $property
        ));
    }

    /**
     * Check the resource has the given property
     *
     * @param string $property
     *
     * @return bool
     */
    public function has($property)
    {
        $property = (string) $property;

        return isset($this->properties[$property]) ||
            isset($this->subResources[$property]);
    }
}
