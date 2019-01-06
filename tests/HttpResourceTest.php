<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    HttpResource,
    HttpResource\Property,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class HttpResourceTest extends TestCase
{
    public function testInterface()
    {
        $resource = new HttpResource(
            'foo',
            $properties = new Map('string', Property::class)
        );

        $this->assertSame('foo', $resource->name());
        $this->assertSame($properties, $resource->properties());
    }

    public function testOf()
    {
        $resource = HttpResource::of('foo', new Property('bar', 42));

        $this->assertInstanceOf(HttpResource::class, $resource);
        $this->assertSame('foo', $resource->name());
        $this->assertCount(1, $resource->properties());
        $this->assertSame(42, $resource->properties()->get('bar')->value());
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenEmptyName()
    {
        new HttpResource(
            '',
            new Map('string', Property::class)
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 2 must be of type MapInterface<string, Innmind\Rest\Client\HttpResource\Property>
     */
    public function testThrowWhenInvalidProperties()
    {
        new HttpResource(
            'foo',
            new Map('string', 'variable')
        );
    }
}
