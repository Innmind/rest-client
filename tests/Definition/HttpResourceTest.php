<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\{
    Definition\HttpResource,
    Definition\Identity,
    Definition\Property,
    Definition\AllowedLink,
    Link,
    Link\Parameter,
    Identity as IdentityInterface,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    Map,
    Set,
};
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
            $links = new Set(AllowedLink::class),
            true
        );

        $this->assertSame('foo', $resource->name());
        $this->assertSame('foo', (string) $resource);
        $this->assertSame($url, $resource->url());
        $this->assertSame($identity, $resource->identity());
        $this->assertSame($properties, $resource->properties());
        $this->assertSame($metas, $resource->metas());
        $this->assertSame($links, $resource->links());
        $this->assertTrue($resource->isRangeable());
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenEmptyName()
    {
        new HttpResource(
            '',
            $this->createMock(UrlInterface::class),
            new Identity('uuid'),
            new Map('string', Property::class),
            new Map('scalar', 'variable'),
            new Set(AllowedLink::class),
            true
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 4 must be of type MapInterface<string, Innmind\Rest\Client\Definition\Property>
     */
    public function testThrowWhenInvalidPropertyMap()
    {
        new HttpResource(
            'foo',
            $this->createMock(UrlInterface::class),
            new Identity('uuid'),
            new Map('int', Property::class),
            new Map('scalar', 'variable'),
            new Set(AllowedLink::class),
            true
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 5 must be of type MapInterface<scalar, variable>
     */
    public function testThrowWhenInvalidMetaMap()
    {
        new HttpResource(
            'foo',
            $this->createMock(UrlInterface::class),
            new Identity('uuid'),
            new Map('string', Property::class),
            new Map('string', 'scalar'),
            new Set(AllowedLink::class),
            true
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 6 must be of type SetInterface<Innmind\Rest\Client\Definition\AllowedLink>
     */
    public function testThrowWhenInvalidLinkMap()
    {
        new HttpResource(
            'foo',
            $this->createMock(UrlInterface::class),
            new Identity('uuid'),
            new Map('string', Property::class),
            new Map('scalar', 'variable'),
            new Set('string'),
            true
        );
    }

    public function testAllowsLink()
    {
        $resource = new HttpResource(
            'foo',
            $this->createMock(UrlInterface::class),
            new Identity('uuid'),
            new Map('string', Property::class),
            new Map('scalar', 'variable'),
            Set::of(
                AllowedLink::class,
                new AllowedLink(
                    'res',
                    'rel',
                    Set::of('string')
                )
            ),
            true
        );

        $notAllowed = Link::of(
            'foo',
            $this->createMock(IdentityInterface::class),
            'baz'
        );
        $allowed = Link::of(
            'res',
            $this->createMock(IdentityInterface::class),
            'rel'
        );

        $this->assertFalse($resource->allowsLink($notAllowed));
        $this->assertTrue($resource->allowsLink($allowed));
    }
}
