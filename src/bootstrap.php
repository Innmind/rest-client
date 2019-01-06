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
    Serializer\Decode,
    Serializer\Encode,
    Serializer\Denormalizer\DenormalizeCapabilitiesNames,
    Serializer\Denormalizer\DenormalizeDefinition,
    Serializer\Denormalizer\DenormalizeResource,
    Serializer\Normalizer\NormalizeDefinition,
    Serializer\Normalizer\NormalizeResource,
    Definition\Types,
    Visitor\ResolveIdentity,
    Translator\Specification\SpecificationTranslator,
    Response\ExtractIdentity,
    Response\ExtractIdentities,
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
    $contentTypes = $contentTypes ?? Formats::of(
        new Format(
            'json',
            Set::of(MediaType::class, new MediaType('application/json', 0)),
            0
        )
    );
    $types = new Types(...($types ?? []));
    $resolveIdentity = new ResolveIdentity($urlResolver);

    $serializer = Serializer::build();

    $denormalizeDefinition = new DenormalizeDefinition($types);

    $decode = new Decode\Json;
    $encode = new Encode\Json;

    return new Client\Client(
        new RetryServerFactory(
            new ServerFactory(
                $transport,
                $urlResolver,
                new ExtractIdentity($resolveIdentity),
                new ExtractIdentities($resolveIdentity),
                new DenormalizeResource,
                new NormalizeResource,
                $encode,
                $decode,
                new SpecificationTranslator,
                $contentTypes,
                new RefreshLimitedFactory(
                    new CacheFactory(
                        $cache,
                        $decode,
                        $encode,
                        new DenormalizeCapabilitiesNames,
                        $denormalizeDefinition,
                        new NormalizeDefinition,
                        new Factory(
                            $transport,
                            $urlResolver,
                            new DefinitionFactory($denormalizeDefinition),
                            $contentTypes
                        )
                    )
                )
            )
        )
    );
}
