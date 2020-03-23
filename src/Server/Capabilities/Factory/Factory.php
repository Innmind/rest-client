<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities\Factory;

use Innmind\Rest\Client\{
    Server\Capabilities as CapabilitiesInterface,
    Server\Capabilities\Capabilities,
    Server\Capabilities\Factory as FactoryInterface,
    Server\DefinitionFactory,
    Formats,
};
use Innmind\Url\UrlInterface;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\HttpTransport\Transport;

final class Factory implements FactoryInterface
{
    private Transport $transport;
    private ResolverInterface $resolver;
    private DefinitionFactory $definitionFactory;
    private Formats $formats;

    public function __construct(
        Transport $transport,
        ResolverInterface $resolver,
        DefinitionFactory $definitionFactory,
        Formats $formats
    ) {
        $this->transport = $transport;
        $this->resolver = $resolver;
        $this->definitionFactory = $definitionFactory;
        $this->formats = $formats;
    }

    public function __invoke(UrlInterface $url): CapabilitiesInterface
    {
        return new Capabilities(
            $this->transport,
            $url,
            $this->resolver,
            $this->definitionFactory,
            $this->formats
        );
    }
}
