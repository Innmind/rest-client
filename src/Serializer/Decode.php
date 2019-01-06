<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Serializer;

use Innmind\Rest\Client\Exception\LogicException;
use Innmind\Stream\Readable;

interface Decode
{
    /**
     * @throws LogicException When format not supported
     */
    public function __invoke(string $format, Readable $content): array;
}
