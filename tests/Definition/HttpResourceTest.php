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
use Innmind\Url\Url;
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
            $url = Url::of('http://example.com/'),
            $identity = new Identity('uuid'),
            $properties = Map::of('string', Property::class),
            $metas = Map::of('scalar', 'variable'),
            $links = Set::of(AllowedLink::class),
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
            Url::of('http://example.com/'),
            new Identity('uuid'),
            Map::of('string', Property::class),
            Map::of('scalar', 'variable'),
            Set::of(AllowedLink::class),
            true
        );
    }

    public function testThrowWhenInvalidPropertyMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 4 must be of type Map<string, Innmind\Rest\Client\Definition\Property>');

        new HttpResource(
            'foo',
            Url::of('http://example.com/'),
            new Identity('uuid'),
            Map::of('int', Property::class),
            Map::of('scalar', 'variable'),
            Set::of(AllowedLink::class),
            true
        );
    }

    public function testThrowWhenInvalidMetaMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 5 must be of type Map<scalar, variable>');

        new HttpResource(
            'foo',
            Url::of('http://example.com/'),
            new Identity('uuid'),
            Map::of('string', Property::class),
            Map::of('string', 'scalar'),
            Set::of(AllowedLink::class),
            true
        );
    }

    public function testThrowWhenInvalidLinkMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 6 must be of type Set<Innmind\Rest\Client\Definition\AllowedLink>');

        new HttpResource(
            'foo',
            Url::of('http://example.com/'),
            new Identity('uuid'),
            Map::of('string', Property::class),
            Map::of('scalar', 'variable'),
            Set::of('string'),
            true
        );
    }

    public function testAllowsLink()
    {
        $resource = new HttpResource(
            'foo',
            Url::of('http://example.com/'),
            new Identity('uuid'),
            Map::of('string', Property::class),
            Map::of('scalar', 'variable'),
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
