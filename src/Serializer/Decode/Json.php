<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Decode;

use Innmind\Rest\Client\{
    Serializer\Decode,
    Exception\LogicException,
};
use Innmind\Stream\Readable;
use Innmind\Json\Json as JsonLib;

final class Json implements Decode
{
    public function __invoke(string $format, Readable $content): array
    {
        if ($format !== 'json') {
            throw new LogicException;
        }

        /** @var array */
        return JsonLib::decode($content->toString());
    }
}
