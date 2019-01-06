<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server\Capabilities\Factory;

use Innmind\Rest\Client\{
    Server\Capabilities\Factory\Factory,
    Server\Capabilities\Factory as FactoryInterface,
    Server\Capabilities,
    Server\DefinitionFactory,
    Serializer\Normalizer\DefinitionNormalizer,
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
    private $factory;

    public function setUp()
    {
        $this->factory = new Factory(
            $this->createMock(Transport::class),
            $this->createMock(ResolverInterface::class),
            new DefinitionFactory(
                new DefinitionNormalizer(new Types)
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
            $this->factory
        );
    }

    public function testMake()
    {
        $this->assertInstanceOf(
            Capabilities::class,
            $this->factory->make(
                $this->createMock(UrlInterface::class)
            )
        );
    }
}
