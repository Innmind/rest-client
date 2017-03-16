<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\Capabilities\Factory,
    Server\Capabilities\FactoryInterface,
    Server\Capabilities,
    Server\DefinitionFactory,
    Serializer\Normalizer\DefinitionNormalizer,
    Definition\Types,
    Formats,
    Format\Format,
    Format\MediaType
};
use Innmind\HttpTransport\TransportInterface;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Url\UrlInterface;
use Innmind\Immutable\{
    Map,
    Set
};
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    private $factory;

    public function setUp()
    {
        $this->factory = new Factory(
            $this->createMock(TransportInterface::class),
            $this->createMock(ResolverInterface::class),
            new DefinitionFactory(
                new DefinitionNormalizer(new Types)
            ),
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
