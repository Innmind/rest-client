<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\Server\{
    Capabilities\RefreshLimitedFactory,
    Capabilities\FactoryInterface,
    CapabilitiesInterface,
    RefreshLimitedCapabilities
};
use Innmind\Url\UrlInterface;
use Symfony\Component\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;

class RefreshLimitedFactoryTest extends TestCase
{
    private $factory;
    private $inner;

    public function setUp()
    {
        $this->factory = new RefreshLimitedFactory(
            $this->inner = $this->createMock(FactoryInterface::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            FactoryInterface::class,
            $this->factory
        );
    }

    public function testMake()
    {
        $url = $this->createMock(UrlInterface::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('make')
            ->with($url)
            ->willReturn($this->createMock(CapabilitiesInterface::class));

        $this->assertInstanceOf(
            RefreshLimitedCapabilities::class,
            $this->factory->make($url)
        );
    }
}
