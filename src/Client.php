<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Translator\SpecificationTranslatorInterface,
    Server\DefinitionFactory,
    Server\Server,
    Server\RetryServer,
    Server\CapabilitiesInterface,
    Server\Capabilities,
    Server\CacheCapabilities
};
use Innmind\HttpTransport\TransportInterface;
use Innmind\Url\{
    UrlInterface,
    Url
};
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Filesystem\AdapterInterface;
use Innmind\Immutable\Map;
use Symfony\Component\Serializer\Serializer;

final class Client implements ClientInterface
{
    private $transport;
    private $resolver;
    private $serializer;
    private $translator;
    private $definitionFactory;
    private $filesystem;
    private $servers;
    private $formats;

    public function __construct(
        TransportInterface $transport,
        ResolverInterface $resolver,
        Serializer $serializer,
        SpecificationTranslatorInterface $translator,
        DefinitionFactory $definitionFactory,
        AdapterInterface $filesystem,
        Formats $formats
    ) {
        $this->transport = $transport;
        $this->resolver = $resolver;
        $this->serializer = $serializer;
        $this->translator = $translator;
        $this->definitionFactory = $definitionFactory;
        $this->filesystem = $filesystem;
        $this->formats = $formats;
        $this->servers = new Map('string', ServerInterface::class);
    }

    public function server(string $url): ServerInterface
    {
        $url = Url::fromString($url);
        $hash = md5((string) $url);

        if ($this->servers->contains($hash)) {
            return $this->servers->get($hash);
        }

        $server = $this->makeServer($url);
        $this->servers = $this->servers->put($hash, $server);

        return $server;
    }

    private function makeServer(UrlInterface $url): ServerInterface
    {
        return new RetryServer(
            new Server(
                $url,
                $this->transport,
                $this->makeCapabilities($url),
                $this->resolver,
                $this->serializer,
                $this->translator,
                $this->formats
            )
        );
    }

    private function makeCapabilities(UrlInterface $url): CapabilitiesInterface
    {
        return new CacheCapabilities(
            new Capabilities(
                $this->transport,
                $url,
                $this->resolver,
                $this->definitionFactory
            ),
            $this->filesystem,
            $this->serializer,
            $url
        );
    }
}
