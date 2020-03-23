<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Link,
    Link\Parameter,
    Identity,
    Exception\DomainException,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function testInterface()
    {
        $link = new Link(
            'foo',
            $identity = $this->createMock(Identity::class),
            'baz',
            $parameters = Map::of('string', Parameter::class)
        );

        $this->assertSame('foo', $link->definition());
        $this->assertSame($identity, $link->identity());
        $this->assertSame('baz', $link->relationship());
        $this->assertSame($parameters, $link->parameters());
    }

    public function testOf()
    {
        $link = Link::of(
            'foo',
            $identity = $this->createMock(Identity::class),
            'baz',
            new Parameter\Parameter('bar', '42')
        );

        $this->assertInstanceOf(Link::class, $link);
        $this->assertSame('foo', $link->definition());
        $this->assertSame($identity, $link->identity());
        $this->assertSame('baz', $link->relationship());
        $this->assertSame('42', $link->parameters()->get('bar')->value());
    }

    public function testNoParametersGiven()
    {
        $link = new Link(
            'foo',
            $this->createMock(Identity::class),
            'baz'
        );

        $this->assertInstanceOf(
            Map::class,
            $link->parameters()
        );
        $this->assertSame('string', (string) $link->parameters()->keyType());
        $this->assertSame(
            Parameter::class,
            (string) $link->parameters()->valueType()
        );
    }

    public function testThrowWhenInvalidParameterMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 4 must be of type Map<string, Innmind\Rest\Client\Link\Parameter>');

        new Link(
            'foo',
            $this->createMock(Identity::class),
            'baz',
            Map::of('string', 'string')
        );
    }

    public function testThrowWhenEmptyDefinition()
    {
        $this->expectException(DomainException::class);

        new Link(
            '',
            $this->createMock(Identity::class),
            'baz',
            Map::of('string', Parameter::class)
        );
    }

    public function testThrowWhenEmptyRelationship()
    {
        $this->expectException(DomainException::class);

        new Link(
            'foo',
            $this->createMock(Identity::class),
            '',
            Map::of('string', Parameter::class)
        );
    }
}
