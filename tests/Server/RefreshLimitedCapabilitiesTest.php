<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server\RefreshLimitedCapabilities,
    Server\CapabilitiesInterface,
    Definition\HttpResource,
    Definition\Property,
    Definition\Identity
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Map
};
use PHPUnit\Framework\TestCase;

class RefreshLimitedCapabilitiesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            CapabilitiesInterface::class,
            new RefreshLimitedCapabilities(
                $this->createMock(CapabilitiesInterface::class)
            )
        );
    }

    public function testNames()
    {
        $capabilities = new RefreshLimitedCapabilities(
            $inner = $this->createMock(CapabilitiesInterface::class)
        );
        $inner
            ->expects($this->once())
            ->method('names')
            ->willReturn(
                $expected = $this->createMock(SetInterface::class)
            );

        $this->assertSame($expected, $capabilities->names());
    }

    public function testGet()
    {
        $capabilities = new RefreshLimitedCapabilities(
            $inner = $this->createMock(CapabilitiesInterface::class)
        );
        $inner
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn(
                $expected = new HttpResource(
                    'foo',
                    $this->createMock(UrlInterface::class),
                    new Identity('uuid'),
                    new Map('string', Property::class),
                    new Map('scalar', 'variable'),
                    new Map('string', 'string'),
                    false
                )
            );

        $this->assertSame($expected, $capabilities->get('foo'));
    }

    public function testDefinitions()
    {
        $capabilities = new RefreshLimitedCapabilities(
            $inner = $this->createMock(CapabilitiesInterface::class)
        );
        $inner
            ->expects($this->once())
            ->method('definitions')
            ->willReturn(
                $expected = $this->createMock(MapInterface::class)
            );

        $this->assertSame($expected, $capabilities->definitions());
    }

    public function testRefreshOnlyOnce()
    {
        $capabilities = new RefreshLimitedCapabilities(
            $inner = $this->createMock(CapabilitiesInterface::class)
        );
        $inner
            ->expects($this->once())
            ->method('refresh');

        $this->assertSame($capabilities, $capabilities->refresh());
        $this->assertSame($capabilities, $capabilities->refresh());
    }
}
