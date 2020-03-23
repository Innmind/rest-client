<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    HttpResource,
    HttpResource\Property,
    Exception\DomainException,
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

    public function testThrowWhenEmptyName()
    {
        $this->expectException(DomainException::class);

        new HttpResource(
            '',
            new Map('string', Property::class)
        );
    }

    public function testThrowWhenInvalidProperties()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type MapInterface<string, Innmind\Rest\Client\HttpResource\Property>');

        new HttpResource(
            'foo',
            new Map('string', 'variable')
        );
    }
}
