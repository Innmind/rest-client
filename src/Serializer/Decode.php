<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer;

use Innmind\Stream\Readable;

interface Decode
{
    public function __invoke(Readable $content): array;
}
