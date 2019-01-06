<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer;

use Innmind\Stream\Readable;

interface Encode
{
    public function __invoke(array $content): Readable;
}
