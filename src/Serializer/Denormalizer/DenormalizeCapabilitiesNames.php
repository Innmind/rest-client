<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Denormalizer;

use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class DenormalizeCapabilitiesNames
{
    public function __invoke(array $data): SetInterface
    {
        return Set::of('string', ...\array_values($data));
    }
}