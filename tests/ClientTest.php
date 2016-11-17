<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Client,
    ClientInterface,
    Translator\SpecificationTranslatorInterface,
    Server\DefinitionFactory,
    Server\RetryServer,
    Serializer\Normalizer\DefinitionNormalizer,
    Definition\Types
};
use Innmind\HttpTransport\TransportInterface;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Filesystem\AdapterInterface;
use Symfony\Component\Serializer\Serializer;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    private $client;

    public function setUp()
    {
        $this->client = new Client(
            $this->createMock(TransportInterface::class),
            $this->createMock(ResolverInterface::class),
            new Serializer,
            $this->createMock(SpecificationTranslatorInterface::class),
            new DefinitionFactory(
                new DefinitionNormalizer(new Types)
            ),
            $this->createMock(AdapterInterface::class)
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
        $server = $this->client->server('http://example.com/');

        $this->assertInstanceOf(RetryServer::class, $server);
        $this->assertSame('http://example.com/', (string) $server->url());
        $this->assertSame($server, $this->client->server('http://example.com'));
        $this->assertNotSame(
            $server,
            $this->client->server('http://example.com/api/')
        );
    }
}
