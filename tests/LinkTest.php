<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Link,
    Link\Parameter,
    Identity
};
use Innmind\Immutable\{
    MapInterface,
    Map
};
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function testInterface()
    {
        $link = new Link(
            'foo',
            $identity = $this->createMock(Identity::class),
            'baz',
            $parameters = new Map('string', Parameter::class)
        );

        $this->assertSame('foo', $link->definition());
        $this->assertSame($identity, $link->identity());
        $this->assertSame('baz', $link->relationship());
        $this->assertSame($parameters, $link->parameters());
    }

    public function testNoParametersGiven()
    {
        $link = new Link(
            'foo',
            $this->createMock(Identity::class),
            'baz'
        );

        $this->assertInstanceOf(
            MapInterface::class,
            $link->parameters()
        );
        $this->assertSame('string', (string) $link->parameters()->keyType());
        $this->assertSame(
            Parameter::class,
            (string) $link->parameters()->valueType()
        );
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage Argument 4 must be of type MapInterface<string, Innmind\Rest\Client\Link\Parameter>
     */
    public function testThrowWhenInvalidParameterMap()
    {
        new Link(
            'foo',
            $this->createMock(Identity::class),
            'baz',
            new Map('string', 'string')
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenEmptyDefinition()
    {
        new Link(
            '',
            $this->createMock(Identity::class),
            'baz',
            new Map('string', Parameter::class)
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenEmptyRelationship()
    {
        new Link(
            'foo',
            $this->createMock(Identity::class),
            '',
            new Map('string', Parameter::class)
        );
    }
}
