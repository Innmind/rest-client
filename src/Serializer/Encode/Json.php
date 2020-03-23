<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer\Encode;

use Innmind\Rest\Client\Serializer\Encode;
use Innmind\Stream\Readable;
use Innmind\Json\Json as JsonLib;
use Innmind\Stream\Readable\Stream;

final class Json implements Encode
{
    public function __invoke(array $content): Readable
    {
        return Stream::ofContent(
            JsonLib::encode($content)
        );
    }
}
