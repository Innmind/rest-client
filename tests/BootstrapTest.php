<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use function Innmind\Rest\Client\bootstrap;
use Innmind\Rest\Client\Client;
use Innmind\HttpTransport\Transport;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Filesystem\Adapter;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap()
    {
        $client = bootstrap(
            $this->createMock(Transport::class),
            $this->createMock(ResolverInterface::class),
            $this->createMock(Adapter::class)
        );

        $this->assertInstanceOf(Client::class, $client);
    }
}
