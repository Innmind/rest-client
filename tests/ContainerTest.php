<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\Client;
use Innmind\Compose\{
    ContainerBuilder\ContainerBuilder,
    Loader\Yaml
};
use Innmind\Url\Path;
use Innmind\Immutable\Map;
use Innmind\HttpTransport\Transport;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Filesystem\Adapter;
use Symfony\Component\Serializer\Serializer;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testService()
    {
        $container = (new ContainerBuilder(new Yaml))(
            new Path('container.yml'),
            (new Map('string', 'mixed'))
                ->put('transport', $this->createMock(Transport::class))
                ->put('urlResolver', $this->createMock(ResolverInterface::class))
                ->put('serializer', new Serializer)
                ->put('cache', $this->createMock(Adapter::class))
        );

        $this->assertInstanceOf(Client::class, $container->get('client'));
    }
}
