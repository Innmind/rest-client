<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Link;

interface ParameterInterface
{
    public function key(): string;
    public function value(): string;
}
