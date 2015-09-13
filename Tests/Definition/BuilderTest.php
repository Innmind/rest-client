<?php

namespace Innmind\Rest\Client\Tests\Definition;

use Innmind\Rest\Client\Definition\Builder;
use Innmind\Rest\Client\Definition\Resource;
use Innmind\Rest\Client\Definition\Property;

class BuilderTest extends \PHPUnit_Framework_TestCase
{
    protected $b;

    public function setUp()
    {
        $this->b = new Builder;
    }

    public function testBuild()
    {
        $def = [
            'id' => 'uuid',
            'properties' => [
                'foo' => [
                    'type' => 'array',
                    'access' => ['READ'],
                    'variants' => ['bar'],
                    'optional' => true,
                    'inner_type' => 'resource',
                    'resource' => [
                        'id' => 'id',
                        'properties' => [
                            'foo' => [
                                'type' => 'string',
                                'access' => ['UPDATE'],
                                'variants' => [],
                            ],
                        ],
                    ],
                ],
                'bar' => [
                    'type' => 'resource',
                    'access' => ['CREATE'],
                    'variants' => [],
                    'resource' => $known = new Resource('uuid', [], []),
                ],
            ],
            'methods' => ['GET'],
        ];

        $resource = $this->b->build($def);

        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertSame(
            'uuid',
            $resource->getId()
        );
        $this->assertSame(
            ['GET'],
            $resource->getMethods()
        );
        $this->assertTrue($resource->hasProperty('foo'));
        $this->assertTrue($resource->hasProperty('bar'));
        $foo = $resource->getProperty('foo');
        $this->assertInstanceOf(Property::class, $foo);
        $this->assertSame(
            'array',
            $foo->getType()
        );
        $this->assertSame(
            ['READ'],
            $foo->getAccess()
        );
        $this->assertSame(
            ['bar'],
            $foo->getVariants()
        );
        $this->assertTrue($foo->isOptional());
        $this->assertSame(
            'resource',
            $foo->getInnerType()
        );
        $inner = $foo->getResource();
        $this->assertSame(
            'id',
            $inner->getId()
        );
        $this->assertTrue($inner->hasProperty('foo'));
        $this->assertSame(
            'string',
            $inner->getProperty('foo')->getType()
        );
        $bar = $resource->getProperty('bar');
        $this->assertSame(
            $known,
            $bar->getResource()
        );
    }
}
