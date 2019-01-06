<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server\Capabilities\Factory;

use Innmind\Rest\Client\{
    Server\Capabilities\Factory\Factory,
    Server\Capabilities\Factory as FactoryInterface,
    Server\Capabilities,
    Server\DefinitionFactory,
    Serializer\Denormalizer\DenormalizeDefinition,
    Serializer\Decode\Json,
    Definition\Types,
    Formats,
    Format\Format,
    Format\MediaType,
};
use Innmind\HttpTransport\Transport;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    private $make;

    public function setUp()
    {
        $this->make = new Factory(
            $this->createMock(Transport::class),
            $this->createMock(ResolverInterface::class),
            new DefinitionFactory(
                new DenormalizeDefinition(new Types),
                new Json
            ),
            Formats::of(
                new Format(
                    'json',
                    Set::of(
                        MediaType::class,
                        new MediaType('application/json', 0)
                    ),
                    1
                )
            )
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            FactoryInterface::class,
            $this->make
        );
    }

    public function testMake()
    {
        $this->assertInstanceOf(
            Capabilities::class,
            ($this->make)(
                $this->createMock(UrlInterface::class)
            )
        );
    }
}
