<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Server as ServerInterface,
    Translator\SpecificationTranslator,
    Formats,
    Response\ExtractIdentity,
    Response\ExtractIdentities,
    Serializer\Denormalizer\DenormalizeResource,
    Serializer\Normalizer\NormalizeResource,
    Serializer\Encode,
    Serializer\Decode,
};
use Innmind\Url\UrlInterface;
use Innmind\HttpTransport\Transport;
use Innmind\UrlResolver\ResolverInterface;

final class ServerFactory implements Factory
{
    private Transport $transport;
    private ResolverInterface $resolver;
    private ExtractIdentity $extractIdentity;
    private ExtractIdentities $extractIdentities;
    private DenormalizeResource $denormalizeResource;
    private NormalizeResource $normalizeResource;
    private Encode $encode;
    private Decode $decode;
    private SpecificationTranslator $translator;
    private Formats $formats;
    private Capabilities\Factory $capabilities;

    public function __construct(
        Transport $transport,
        ResolverInterface $resolver,
        ExtractIdentity $extractIdentity,
        ExtractIdentities $extractIdentities,
        DenormalizeResource $denormalizeResource,
        NormalizeResource $normalizeResource,
        Encode $encode,
        Decode $decode,
        SpecificationTranslator $translator,
        Formats $formats,
        Capabilities\Factory $capabilities
    ) {
        $this->transport = $transport;
        $this->resolver = $resolver;
        $this->extractIdentity = $extractIdentity;
        $this->extractIdentities = $extractIdentities;
        $this->denormalizeResource = $denormalizeResource;
        $this->normalizeResource = $normalizeResource;
        $this->encode = $encode;
        $this->decode = $decode;
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
            $this->denormalizeResource,
            $this->normalizeResource,
            $this->encode,
            $this->decode,
            $this->translator,
            $this->formats
        );
    }
}
