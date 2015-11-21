<?php

namespace Innmind\Rest\Client\Definition;

class ResourceDefinition
{
    protected $url;
    protected $id;
    protected $properties;
    protected $meta;
    protected $isFresh;

    public function __construct(
        $url,
        $id,
        array $properties,
        array $meta = [],
        $isFresh = false
    ) {
        $this->url = (string) $url;
        $this->id = (string) $id;
        $this->properties = $properties;
        $this->meta = $meta;
        $this->isFresh = (bool) $isFresh;

        $this->verifyProperties($properties);
    }

    /**
     * Return the url of the resource definition
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Check if the given url is attached to this resource definition
     *
     * @param string $url
     *
     * @return bool
     */
    public function belongsTo($url)
    {
        $pattern = sprintf(
            '/^%s\/[^\/]*$/',
            str_replace('/', '\/', rtrim($this->url, '/'))
        );
        $matches = [];
        preg_match($pattern, $url, $matches);

        return count($matches) > 0;
    }

    /**
     * Return the property name used as id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return all the properties
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Check if it has a property
     *
     * @param string $property
     *
     * @return bool
     */
    public function hasProperty($property)
    {
        return isset($this->properties[(string) $property]);
    }

    /**
     * Return the property definition
     *
     * @param string $property
     *
     * @return Property
     */
    public function getProperty($property)
    {
        if (!$this->hasProperty($property)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown property %s',
                $property
            ));
        }

        return $this->properties[(string) $property];
    }

    /**
     * Return all the metas
     *
     * @return array
     */
    public function getMetas()
    {
        return $this->meta;
    }

    /**
     * Check if a meta exists
     *
     * @param string $meta
     *
     * @return bool
     */
    public function hasMeta($meta)
    {
        return array_key_exists((string) $meta, $this->meta);
    }

    /**
     * Return a meta
     *
     * @param string $meta
     *
     * @return mixed
     */
    public function getMeta($meta)
    {
        if (!$this->hasMeta($meta)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown meta %s',
                $meta
            ));
        }

        return $this->meta[(string) $meta];
    }

    /**
     * Check if the configuration has been actualised
     * from the server in this process
     *
     * @return bool
     */
    public function isFresh()
    {
        return $this->isFresh;
    }

    /**
     * Replace all informations of the resource with this new definition
     *
     * @param string $id
     * @param array $properties
     * @param array $meta
     *
     * @return ResourceDefinition self
     */
    public function refresh($id, array $properties, array $meta = [])
    {
        $this->id = (string) $id;
        $this->properties = $properties;
        $this->meta = $meta;
        $this->isFresh = true;

        $this->verifyProperties($properties);

        return $this;
    }

    /**
     * Check the array of properties contains only Property objects
     *
     * @param array $properties
     *
     * @return void
     */
    protected function verifyProperties(array $properties)
    {
        foreach ($properties as $property) {
            if (!$property instanceof Property) {
                throw new \InvalidArgumentException(sprintf(
                    'A resource property must be a Property object (%s given)',
                    gettype($property)
                ));
            }
        }
    }
}
