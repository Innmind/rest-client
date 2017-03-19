<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server\ServerFactory,
    Server\FactoryInterface,
    Server\Server,
    Server\Capabilities\FactoryInterface as CapabilitiesFactoryInterface,
    ServerInterface,
    Translator\SpecificationTranslatorInterface,
    Formats,
    Format\Format,
    Format\MediaType
};
use Innmind\Url\UrlInterface;
use Innmind\HttpTransport\TransportInterface;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Immutable\{
    Map,
    Set
};
use Symfony\Component\Serializer\Serializer;
use PHPUnit\Framework\TestCase;

class ServerFactoryTest extends TestCase
{
    private $factory;
    private $capabilities;

    public function setUp()
    {
        $this->factory = new ServerFactory(
            $this->createMock(TransportInterface::class),
            $this->createMock(ResolverInterface::class),
            $this->createMock(Serializer::class),
            $this->createMock(SpecificationTranslatorInterface::class),
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
            ),
            $this->capabilities = $this->createMock(CapabilitiesFactoryInterface::class)
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            FactoryInterface::class,
            $this->factory
        );
    }

    public function testMake()
    {
        $url = $this->createMock(UrlInterface::class);
        $this
            ->capabilities
            ->expects($this->once())
            ->method('make')
            ->with($url);

        $this->assertInstanceOf(
            Server::class,
            $this->factory->make($url)
        );
    }
}
