<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Definition\{
    HttpResource,
    Identity,
    Property
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class HttpResourceTest extends TestCase
{
    public function testInterface()
    {
        $resource = new HttpResource(
            'foo',
            $url = $this->createMock(UrlInterface::class),
            $identity = new Identity('uuid'),
            $properties = new Map('string', Property::class),
            $metas = new Map('scalar', 'variable'),
            true
        );

        $this->assertSame('foo', $resource->name());
        $this->assertSame('foo', (string) $resource);
        $this->assertSame($url, $resource->url());
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
            $this->createMock(UrlInterface::class),
            new Identity('uuid'),
            new Map('string', Property::class),
            new Map('scalar', 'variable'),
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
            $this->createMock(UrlInterface::class),
            new Identity('uuid'),
            new Map('int', Property::class),
            new Map('scalar', 'variable'),
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
            $this->createMock(UrlInterface::class),
            new Identity('uuid'),
            new Map('string', Property::class),
            new Map('string', 'scalar'),
            true
        );
    }
}
