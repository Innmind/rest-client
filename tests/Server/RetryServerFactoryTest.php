<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\Server\{
    RetryServerFactory,
    Factory,
    RetryServer,
};
use Innmind\Url\Url;
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
        $make = new RetryServerFactory(
            $inner = $this->createMock(Factory::class)
        );
        $url = Url::of('http://example.com/');
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($url);

        $this->assertInstanceOf(
            RetryServer::class,
            $make($url)
        );
    }
}
