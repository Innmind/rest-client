<?php

namespace Innmind\Rest\Client\Definition;

class Builder
{
    /**
     * Transform an array representation of a resource to an object
     *
     * @param array $definition
     *
     * @return Innmind\Rest\Client\Definition\Resource
     */
    public function build(array $definition)
    {
        return new Resource(
            $definition['url'],
            $definition['id'],
            $this->buildProperties($definition['properties']),
            isset($definition['meta']) ? $definition['meta'] : [],
            true
        );
    }

    /**
     * Build the array of properties
     *
     * @param array $definition
     *
     * @return array
     */
    protected function buildProperties(array $definition)
    {
        $properties = [];

        foreach ($definition as $prop => $def) {
            $properties[$prop] = $this->buildProperty($prop, $def);
        }

        return $properties;
    }

    /**
     * Build a property
     *
     * @param string $name
     * @param array $definition
     *
     * @return Property
     */
    protected function buildProperty($name, array $definition)
    {
        $property = new Property(
            $name,
            $definition['type'],
            $definition['access'],
            $definition['variants'],
            isset($definition['optional']) ? $definition['optional'] : false,
            isset($definition['inner_type']) ? $definition['inner_type'] : null
        );

        if ($property->containsResource()) {
            if ($definition['resource'] instanceof Resource) {
                $resource = $definition['resource'];
            } else {
                $resource = $this->build($definition['resource']);
            }

            $property->linkTo($resource);
        }

        return $property;
    }
}
