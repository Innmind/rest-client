<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    HttpResource,
    HttpResource\Property
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

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyName()
    {
        new HttpResource(
            '',
            new Map('string', Property::class)
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidProperties()
    {
        new HttpResource(
            'foo',
            new Map('string', 'variable')
        );
    }
}
