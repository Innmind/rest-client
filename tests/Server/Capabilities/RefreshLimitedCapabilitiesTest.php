<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities\RefreshLimitedCapabilities,
    Server\Capabilities,
    Definition\HttpResource,
    Definition\Property,
    Definition\Identity,
    Definition\AllowedLink,
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class RefreshLimitedCapabilitiesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Capabilities::class,
            new RefreshLimitedCapabilities(
                $this->createMock(Capabilities::class)
            )
        );
    }

    public function testNames()
    {
        $capabilities = new RefreshLimitedCapabilities(
            $inner = $this->createMock(Capabilities::class)
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
            $inner = $this->createMock(Capabilities::class)
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
                    new Set(AllowedLink::class),
                    false
                )
            );

        $this->assertSame($expected, $capabilities->get('foo'));
    }

    public function testDefinitions()
    {
        $capabilities = new RefreshLimitedCapabilities(
            $inner = $this->createMock(Capabilities::class)
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
            $inner = $this->createMock(Capabilities::class)
        );
        $inner
            ->expects($this->once())
            ->method('refresh');

        $this->assertSame($capabilities, $capabilities->refresh());
        $this->assertSame($capabilities, $capabilities->refresh());
    }
}
