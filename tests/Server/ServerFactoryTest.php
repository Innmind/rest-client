<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server\ServerFactory,
    Server\Factory,
    Server\Server,
    Server\Capabilities\Factory as CapabilitiesFactoryInterface,
    Translator\SpecificationTranslator,
    Formats,
    Format\Format,
    Format\MediaType,
    Response\ExtractIdentity,
    Response\ExtractIdentities,
    Visitor\ResolveIdentity,
    Serializer\Denormalizer\DenormalizeResource,
    Serializer\Normalizer\NormalizeResource,
    Serializer\Encode,
    Serializer\Decode,
};
use Innmind\Url\Url;
use Innmind\HttpTransport\Transport;
use Innmind\UrlResolver\Resolver;
use Innmind\Immutable\{
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class ServerFactoryTest extends TestCase
{
    private $make;
    private $capabilities;

    public function setUp(): void
    {
        $this->make = new ServerFactory(
            $this->createMock(Transport::class),
            $resolver = $this->createMock(Resolver::class),
            new ExtractIdentity(new ResolveIdentity($resolver)),
            new ExtractIdentities(new ResolveIdentity($resolver)),
            new DenormalizeResource,
            new NormalizeResource,
            new Encode\Json,
            new Decode\Json,
            $this->createMock(SpecificationTranslator::class),
            Formats::of(
                new Format(
                    'json',
                    Set::of(
                        MediaType::class,
                        new MediaType('application/json', 0)
                    ),
                    1
                )
            ),
            $this->capabilities = $this->createMock(CapabilitiesFactoryInterface::class)
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
        $url = Url::of('http://example.com/');
        $this
            ->capabilities
            ->expects($this->once())
            ->method('__invoke')
            ->with($url);

        $this->assertInstanceOf(
            Server::class,
            ($this->make)($url)
        );
    }
}
