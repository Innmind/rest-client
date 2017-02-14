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
    Definition\Types,
    Formats,
    Format\Format,
    Format\MediaType
};
use Innmind\HttpTransport\TransportInterface;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Filesystem\AdapterInterface;
use Innmind\Immutable\{
    Map,
    Set
};
use Symfony\Component\Serializer\Serializer;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
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
            $this->createMock(AdapterInterface::class),
            new Formats(
                (new Map('string', Format::class))
                    ->put(
                        'json',
                        new Format(
                            'json',
                            (new Set(MediaType::class))->add(
                                new MediaType('application/json', 0)
                            ),
                            1
                        )
                    )
            )
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
