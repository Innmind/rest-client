<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Decode;

use Innmind\Rest\Client\Serializer\Decode;
use Innmind\Stream\Readable;
use Innmind\Json\Json as JsonLib;

final class Json implements Decode
{
    public function __invoke(Readable $content): array
    {
        return JsonLib::decode((string) $content);
    }
}
