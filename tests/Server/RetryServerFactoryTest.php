<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\Server\{
    RetryServerFactory,
    FactoryInterface,
    RetryServer
};
use Innmind\Url\UrlInterface;
use PHPUnit\Framework\TestCase;

class RetryServerFactoryTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            FactoryInterface::class,
            new RetryServerFactory(
                $this->createMock(FactoryInterface::class)
            )
        );
    }

    public function testMake()
    {
        $factory = new RetryServerFactory(
            $inner = $this->createMock(FactoryInterface::class)
        );
        $url = $this->createMock(UrlInterface::class);
        $inner
            ->expects($this->once())
            ->method('make')
            ->with($url);

        $this->assertInstanceOf(
            RetryServer::class,
            $factory->make($url)
        );
    }
}
