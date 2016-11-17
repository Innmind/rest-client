<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

interface ClientInterface
{
    public function server(string $server): ServerInterface;
}
