<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Format\Format,
    Format\MediaType,
    Server\RetryServerFactory,
    Server\ServerFactory,
    Server\Capabilities\RefreshLimitedFactory,
    Server\Capabilities\CacheFactory,
    Server\Capabilities\Factory\Factory,
    Server\DefinitionFactory,
    Serializer\Normalizer,
    Definition\Types,
    Visitor\ResolveIdentity,
    Translator\Specification\SpecificationTranslator,
};
use Innmind\HttpTransport\Transport;
use Innmind\UrlResolver\ResolverInterface;
use Innmind\Filesystem\Adapter;
use Innmind\Immutable\{
    SetInterface,
    Set,
    Map,
};

function bootstrap(
    Transport $transport,
    ResolverInterface $urlResolver,
    Adapter $cache,
    SetInterface $types = null,
    Formats $contentTypes = null
): Client {
    $contentTypes = $contentTypes ?? new Formats(
        Map::of('string', Format::class)
            ('json', new Format(
                'json',
                Set::of(MediaType::class, new MediaType('application/json', 0)),
                0
            ))
    );
    $types = new Types(...($types ?? []));
    $resolveIdentity = new ResolveIdentity($urlResolver);

    $serializer = Serializer::build(
        new Normalizer\CapabilitiesNamesNormalizer,
        $definitionNormalizer = new Normalizer\DefinitionNormalizer($types),
        new Normalizer\IdentitiesNormalizer($resolveIdentity),
        new Normalizer\IdentityNormalizer($resolveIdentity),
        new Normalizer\ResourceNormalizer
    );

    return new Client\Client(
        new RetryServerFactory(
            new ServerFactory(
                $transport,
                $urlResolver,
                $serializer,
                new SpecificationTranslator,
                $contentTypes,
                new RefreshLimitedFactory(
                    new CacheFactory(
                        $cache,
                        $serializer,
                        new Factory(
                            $transport,
                            $urlResolver,
                            new DefinitionFactory($definitionNormalizer),
                            $contentTypes
                        )
                    )
                )
            )
        )
    );
}
