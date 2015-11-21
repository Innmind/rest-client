<?php

namespace Innmind\Rest\Client\Definition;

class Property
{
    protected $name;
    protected $type;
    protected $access;
    protected $variants;
    protected $optional;
    protected $innerType;
    protected $resource;

    public function __construct(
        $name,
        $type,
        array $access,
        array $variants,
        $optional = false,
        $innerType = null
    ) {
        $this->name = (string) $name;
        $this->type = (string) $type;
        $this->access = $access;
        $this->variants = $variants;
        $this->optional = (bool) $optional;
        $this->innerType = $innerType;
    }

    /**
     * Return the property name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the type of the property
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return all the accesses
     *
     * @return array
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * Check if the property has the given access
     *
     * @param string $access
     *
     * @return boolean
     */
    public function hasAccess($access)
    {
        return in_array((string) $access, $this->access, true);
    }

    /**
     * Return all the variants of the property name
     *
     * @return array
     */
    public function getVariants()
    {
        return $this->variants;
    }

    /**
     * Check if property is a variant of the given name
     *
     * @param string $variant
     *
     * @return bool
     */
    public function isVariantOf($variant)
    {
        if ((string) $variant === $this->name) {
            return true;
        }

        return in_array((string) $variant, $this->variants, true);
    }

    /**
     * Check if this is an optional field
     *
     * @return bool
     */
    public function isOptional()
    {
        return $this->optional;
    }

    /**
     * Return the inner type in case it's an array
     *
     * @return string
     */
    public function getInnerType()
    {
        return $this->innerType;
    }

    /**
     * Associate this property to another resource
     *
     * @param ResourceDefinition $resource
     *
     * @return Property self
     */
    public function linkTo(ResourceDefinition $resource)
    {
        if (!$this->containsResource()) {
            throw new \BadMethodCallException(
                'A property can be linked to a resource ' .
                'only if it is of type "resource"'
            );
        }

        $this->resource = $resource;

        return $this;
    }

    /**
     * Check if the property contains a resource
     *
     * @return bool
     */
    public function containsResource()
    {
        return $this->type === 'resource' ||
            ($this->type === 'array' && $this->innerType === 'resource');
    }

    /**
     * Return the resource definition the property is linked to
     *
     * @return ResourceDefinition
     */
    public function getResource()
    {
        if (!$this->containsResource()) {
            throw new \BadMethodCallException(sprintf(
                'The property %s is not linked to a resource',
                $this->name
            ));
        }

        return $this->resource;
    }

    public function __toString()
    {
        return $this->name;
    }
}
