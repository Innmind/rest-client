<?php

namespace Innmind\Rest\Client\Cache;

use Innmind\Rest\Client\Definition\Resource;
use Innmind\Rest\Client\Definition\Property;
use Symfony\Component\Filesystem\Filesystem;

class FileCache extends InMemoryCache
{
    protected $filePath;
    protected $fs;
    protected $resources = [];
    protected $built;

    public function __construct($filePath)
    {
        $this->filePath = (string) $filePath;
        $this->fs = new Filesystem;

        if ($this->fs->exists($this->filePath)) {
            $this->resources = require $this->filePath;
        }
    }

    /**
     * Dump all the resources definitions to the specified file
     *
     * @return void
     */
    public function __destruct()
    {
        $code = <<<EOF
<?php

use Innmind\Rest\Client\Definition\Resource;
use Innmind\Rest\Client\Definition\Property;

EOF;
        $this->built = new \SplObjectStorage;

        foreach ($this->resources as $key => $resource) {
            if ($this->built->contains($resource)) {
                continue;
            }

            $code .= $this->buildResourceCode($key, $resource);
            $this->built->attach($resource);
        }

        $code .= <<<EOF

return [
EOF;

        foreach ($this->resources as $key => $resource) {
            $var = $this->getResourceVarName($key);
            $code .= <<<EOF

    '$key' => \$$var,
EOF;
        }

        $code .= <<<EOF

];
EOF;

        $this->fs->dumpFile($this->filePath, $code);
    }

    /**
     * Return code to build a resource
     *
     * @param string $key
     * @param Innmind\Rest\Client\Definition\Resource $resource
     *
     * @return string
     */
    protected function buildResourceCode($key, Resource $resource)
    {
        $neededCode = '';
        $id = $resource->getId();
        $var = $this->getResourceVarName($key);
        $code = <<<EOF

\$$var = new Resource(
    '$id',
    [
EOF;

        foreach ($resource->getProperties() as $prop) {
            if (
                $prop->containsResource() &&
                !$this->built->contains($prop->getResource())
            ) {
                $neededCode .= $this->buildResourceCode(
                    array_search($prop->getResource(), $this->resources),
                    $prop->getResource()
                );
                $this->built->attach($prop->getResource());
            }
            $code .= $this->buildPropertyCode($prop);
        }

        $methods = var_export($resource->getMethods(), true);
        $meta = var_export($resource->getMetas(), true);
        $code .= <<<EOF

    ],
    $methods,
    $meta
);
EOF;

        return $neededCode . $code;
    }

    /**
     * Return code to build a property
     *
     * @param Property $prop
     *
     * @return string
     */
    protected function buildPropertyCode(Property $prop)
    {
        $name = (string) $prop;
        $type = $prop->getType();
        $access = var_export($prop->getAccess(), true);
        $variants = var_export($prop->getVariants(), true);
        $optional = var_export($prop->isOptional(), true);
        $innerType = null;

        if ($prop->getType() === 'array') {
            $innerType = $prop->getInnerType();
        }

        $innerType = var_export($innerType, true);

        if ($prop->containsResource()) {
            $link = array_search(
                $prop->getResource(),
                $this->resources
            );
            $link = sprintf('->linkTo($%s)', $this->getResourceVarName($link));
        } else {
            $link = '';
        }


        return <<<EOF

        '$name' => (new Property(
            '$name',
            '$type',
            $access,
            $variants,
            $optional,
            $innerType
        ))$link,
EOF;
    }

    /**
     * Build a safe variable name
     *
     * @param string $key
     *
     * @return string
     */
    protected function getResourceVarName($key)
    {
        return sprintf(
            'var%s',
            md5($key)
        );
    }
}
