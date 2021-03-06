<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Client;

use Innmind\Rest\Client\{
    Client\Client,
    Client as ClientInterface,
    Server\Factory,
    Server,
};
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private $client;
    private $factory;

    public function setUp(): void
    {
        $this->client = new Client(
            $this->factory = $this->createMock(Factory::class)
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
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [$this->callback(static function($url): bool {
                    return $url->toString() === 'http://example.com/';
                })],
                [$this->callback(static function($url): bool {
                    return $url->toString() === 'http://example.com/api/';
                })],
            );

        $server = $this->client->server('http://example.com/');

        $this->assertInstanceOf(Server::class, $server);
        $this->assertSame($server, $this->client->server('http://example.com'));
        $this->assertNotSame(
            $server,
            $this->client->server('http://example.com/api/')
        );
    }
}
