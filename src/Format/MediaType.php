<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Format;

use Innmind\Filesystem\MediaType\MediaType as FilesystemMediaType;

final class MediaType
{
    private FilesystemMediaType $mime;
    private int $priority;

    public function __construct(string $mime, int $priority)
    {
        $this->mime = FilesystemMediaType::fromString($mime);
        $this->priority = $priority;
    }

    public function topLevel(): string
    {
        return $this->mime->topLevel();
    }

    public function subType(): string
    {
        return $this->mime->subType();
    }

    public function suffix(): string
    {
        return $this->mime->suffix();
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function __toString(): string
    {
        return (string) $this->mime;
    }
}
