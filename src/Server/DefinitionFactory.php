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
    StatusCode,
};
use Innmind\Url\Url;
use function Innmind\Immutable\first;

final class DefinitionFactory
{
    private DenormalizeDefinition $denormalize;
    private Decode $decode;

    public function __construct(
        DenormalizeDefinition $denormalize,
        Decode $decode
    ) {
        $this->denormalize = $denormalize;
        $this->decode = $decode;
    }

    public function __invoke(
        string $name,
        Url $url,
        Response $response
    ): HttpResource {
        $headers = $response->headers();

        if (
            $response->statusCode()->value() !== StatusCode::codes()->get('OK') ||
            !$headers->contains('Content-Type') ||
            first($headers->get('Content-Type')->values())->toString() !== 'application/json'
        ) {
            throw new DomainException;
        }

        /** @var array{metas: array<scalar, scalar|array>, properties: array<string, array{variants: list<string>, type: string, access: list<string>, optional: bool}>, linkable_to: list<array{resource_path: string, relationship: string, parameters: list<string>}>, identity: string, rangeable: bool} */
        $data = ($this->decode)('json', $response->body());
        $data['url'] = $url->toString();

        return ($this->denormalize)($data, $name);
    }
}
