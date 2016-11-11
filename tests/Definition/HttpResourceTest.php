<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Definition\{
    HttpResource,
    Identity,
    Property
};
use Innmind\Immutable\Map;

class HttpResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $resource = new HttpResource(
            'foo',
            $identity = new Identity('uuid'),
            $properties = new Map('string', Property::class),
            $metas = new Map('string', 'variable'),
            true
        );

        $this->assertSame('foo', $resource->name());
        $this->assertSame('foo', (string) $resource);
        $this->assertSame($identity, $resource->identity());
        $this->assertSame($properties, $resource->properties());
        $this->assertSame($metas, $resource->metas());
        $this->assertTrue($resource->isRangeable());
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyName()
    {
        new HttpResource(
            '',
            new Identity('uuid'),
            new Map('string', Property::class),
            new Map('string', 'variable'),
            true
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidPropertyMap()
    {
        new HttpResource(
            'foo',
            new Identity('uuid'),
            new Map('int', Property::class),
            new Map('string', 'variable'),
            true
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidMetaMap()
    {
        new HttpResource(
            'foo',
            new Identity('uuid'),
            new Map('string', Property::class),
            new Map('string', 'scalar'),
            true
        );
    }
}
