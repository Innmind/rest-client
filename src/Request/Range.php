<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Request;

use Innmind\Rest\Client\Exception\DomainException;

final class Range
{
    private int $firstPosition;
    private int $lastPosition;

    public function __construct(int $firstPosition, int $lastPosition)
    {
        if (
            $firstPosition < 0 ||
            $lastPosition < $firstPosition
        ) {
            throw new DomainException;
        }

        $this->firstPosition = $firstPosition;
        $this->lastPosition = $lastPosition;
    }

    public function firstPosition(): int
    {
        return $this->firstPosition;
    }

    public function lastPosition(): int
    {
        return $this->lastPosition;
    }
}
