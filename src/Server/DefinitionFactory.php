<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\{
    Definition\HttpResource,
    Serializer\Denormalizer\DenormalizeDefinition,
    Serializer\Decode,
    Exception\DomainException,
};
use Innmind\Http\Message\{
    Response,
    StatusCode\StatusCode,
};
use Innmind\Url\UrlInterface;

final class DefinitionFactory
{
    private $denormalize;
    private $decode;

    public function __construct(
        DenormalizeDefinition $denormalize,
        Decode $decode
    ) {
        $this->denormalize = $denormalize;
        $this->decode = $decode;
    }

    public function __invoke(
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

        $data = ($this->decode)('json', $response->body());
        $data['url'] = (string) $url;

        return ($this->denormalize)($data, $name);
    }
}
