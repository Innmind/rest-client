<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server\Capabilities;

use Innmind\Rest\Client\{
    Server\CapabilitiesInterface,
    Server\Capabilities,
    Server\DefinitionFactory,
    Formats
};
use Innmind\Url\UrlInterface;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\HttpTransport\TransportInterface;

final class Factory implements FactoryInterface
{
    private $transport;
    private $resolver;
    private $definitionFactory;
    private $formats;

    public function __construct(
        TransportInterface $transport,
        ResolverInterface $resolver,
        DefinitionFactory $definitionFactory,
        Formats $formats
    ) {
        $this->transport = $transport;
        $this->resolver = $resolver;
        $this->definitionFactory = $definitionFactory;
        $this->formats = $formats;
    }

    public function make(UrlInterface $url): CapabilitiesInterface
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
