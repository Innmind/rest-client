<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Symfony\Component\Serializer\{
    Serializer as SfSerializer,
    Encoder\JsonEncoder,
};

final class Serializer
{
    public static function build(...$normalizers): SfSerializer
    {
        return new SfSerializer(
            $normalizers,
            [new JsonEncoder]
        );
    }
}
