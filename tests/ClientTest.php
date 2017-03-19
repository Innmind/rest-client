<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Client,
    ClientInterface,
    Server\FactoryInterface,
    ServerInterface
};
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private $client;
    private $factory;

    public function setUp()
    {
        $this->client = new Client(
            $this->factory = $this->createMock(FactoryInterface::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            ClientInterface::class,
            $this->client
        );
    }

    public function testServer()
    {
        $this
            ->factory
            ->expects($this->at(0))
            ->method('make')
            ->with($this->callback(function($url): bool {
                return (string) $url === 'http://example.com/';
            }));
        $this
            ->factory
            ->expects($this->at(1))
            ->method('make')
            ->with($this->callback(function($url): bool {
                return (string) $url === 'http://example.com/api/';
            }));

        $server = $this->client->server('http://example.com/');

        $this->assertInstanceOf(ServerInterface::class, $server);
        $this->assertSame($server, $this->client->server('http://example.com'));
        $this->assertNotSame(
            $server,
            $this->client->server('http://example.com/api/')
        );
    }
}
