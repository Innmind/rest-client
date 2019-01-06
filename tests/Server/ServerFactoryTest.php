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
    Visitor\ResolveIdentity,
};
use Innmind\Url\UrlInterface;
use Innmind\HttpTransport\Transport;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Immutable\{
    Map,
    Set,
};
use Symfony\Component\Serializer\Serializer;
use PHPUnit\Framework\TestCase;

class ServerFactoryTest extends TestCase
{
    private $make;
    private $capabilities;

    public function setUp()
    {
        $this->make = new ServerFactory(
            $this->createMock(Transport::class),
            $resolver = $this->createMock(ResolverInterface::class),
            new ExtractIdentity(new ResolveIdentity($resolver)),
            $this->createMock(Serializer::class),
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
        $url = $this->createMock(UrlInterface::class);
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
