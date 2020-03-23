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
    Exception\DomainException,
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

    public function testThrowWhenEmptyName()
    {
        $this->expectException(DomainException::class);

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

    public function testThrowWhenInvalidPropertyMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 4 must be of type MapInterface<string, Innmind\Rest\Client\Definition\Property>');

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

    public function testThrowWhenInvalidMetaMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 5 must be of type MapInterface<scalar, variable>');

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

    public function testThrowWhenInvalidLinkMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 6 must be of type SetInterface<Innmind\Rest\Client\Definition\AllowedLink>');

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
