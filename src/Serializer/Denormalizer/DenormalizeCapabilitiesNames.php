<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Denormalizer;

use Innmind\Immutable\Set;

final class DenormalizeCapabilitiesNames
{
    /**
     * @param list<string> $data
     *
     * @return Set<string>
     */
    public function __invoke(array $data): Set
    {
        return Set::strings(...\array_values($data));
    }
}
