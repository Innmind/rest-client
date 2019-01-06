<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\Server\{
    RetryServerFactory,
    Factory,
    RetryServer,
};
use Innmind\Url\UrlInterface;
use PHPUnit\Framework\TestCase;

class RetryServerFactoryTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Factory::class,
            new RetryServerFactory(
                $this->createMock(Factory::class)
            )
        );
    }

    public function testMake()
    {
        $factory = new RetryServerFactory(
            $inner = $this->createMock(Factory::class)
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
