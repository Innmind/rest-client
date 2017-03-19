<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Link,
    Link\ParameterInterface,
    IdentityInterface
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
            $identity = $this->createMock(IdentityInterface::class),
            'baz',
            $parameters = new Map('string', ParameterInterface::class)
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
            $this->createMock(IdentityInterface::class),
            'baz'
        );

        $this->assertInstanceOf(
            MapInterface::class,
            $link->parameters()
        );
        $this->assertSame('string', (string) $link->parameters()->keyType());
        $this->assertSame(
            ParameterInterface::class,
            (string) $link->parameters()->valueType()
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidParameterMap()
    {
        new Link(
            'foo',
            $this->createMock(IdentityInterface::class),
            'baz',
            new Map('string', 'string')
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyDefinition()
    {
        new Link(
            '',
            $this->createMock(IdentityInterface::class),
            'baz',
            new Map('string', ParameterInterface::class)
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyRelationship()
    {
        new Link(
            'foo',
            $this->createMock(IdentityInterface::class),
            '',
            new Map('string', ParameterInterface::class)
        );
    }
}
