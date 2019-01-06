<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server as ServerInterface,
    Translator\SpecificationTranslator,
    Formats,
    Response\ExtractIdentity,
    Response\ExtractIdentities,
};
use Innmind\Url\UrlInterface;
use Innmind\HttpTransport\Transport;
use Innmind\UrlResolver\ResolverInterface;
use Symfony\Component\Serializer\Serializer;

final class ServerFactory implements Factory
{
    private $transport;
    private $resolver;
    private $extractIdentity;
    private $extractIdentities;
    private $serializer;
    private $translator;
    private $formats;
    private $capabilities;

    public function __construct(
        Transport $transport,
        ResolverInterface $resolver,
        ExtractIdentity $extractIdentity,
        ExtractIdentities $extractIdentities,
        Serializer $serializer,
        SpecificationTranslator $translator,
        Formats $formats,
        Capabilities\Factory $capabilities
    ) {
        $this->transport = $transport;
        $this->resolver = $resolver;
        $this->extractIdentity = $extractIdentity;
        $this->extractIdentities = $extractIdentities;
        $this->serializer = $serializer;
        $this->translator = $translator;
        $this->formats = $formats;
        $this->capabilities = $capabilities;
    }

    public function __invoke(UrlInterface $url): ServerInterface
    {
        return new Server(
            $url,
            $this->transport,
            ($this->capabilities)($url),
            $this->resolver,
            $this->extractIdentity,
            $this->extractIdentities,
            $this->serializer,
            $this->translator,
            $this->formats
        );
    }
}
