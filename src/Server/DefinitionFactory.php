<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Definition\HttpResource,
    Serializer\Normalizer\DefinitionNormalizer,
    Exception\DomainException
};
use Innmind\Http\Message\{
    Response,
    StatusCode\StatusCode
};
use Innmind\Url\UrlInterface;

final class DefinitionFactory
{
    private $denormalizer;

    public function __construct(DefinitionNormalizer $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    public function make(
        string $name,
        UrlInterface $url,
        Response $response
    ): HttpResource {
        $headers = $response->headers();

        if (
            $response->statusCode()->value() !== StatusCode::codes()->get('OK') ||
            !$headers->has('Content-Type') ||
            (string) $headers->get('Content-Type')->values()->current() !== 'application/json'
        ) {
            throw new DomainException;
        }

        $data = json_decode((string) $response->body(), true);
        $data['url'] = (string) $url;

        return $this->denormalizer->denormalize(
            $data,
            HttpResource::class,
            null,
            ['name' => $name]
        );
    }
}
