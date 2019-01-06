<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\Server\{
    Capabilities\RefreshLimitedFactory,
    Capabilities\Factory,
    Capabilities,
    Capabilities\RefreshLimitedCapabilities,
};
use Innmind\Url\UrlInterface;
use Symfony\Component\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;

class RefreshLimitedFactoryTest extends TestCase
{
    private $make;
    private $inner;

    public function setUp()
    {
        $this->make = new RefreshLimitedFactory(
            $this->inner = $this->createMock(Factory::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            Factory::class,
            $this->make
        );
    }

    public function testMake()
    {
        $url = $this->createMock(UrlInterface::class);
        $this
            ->inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($url)
            ->willReturn($this->createMock(Capabilities::class));

        $this->assertInstanceOf(
            RefreshLimitedCapabilities::class,
            ($this->make)($url)
        );
    }
}
