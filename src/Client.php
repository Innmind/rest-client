<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

interface Client
{
    public function server(string $server): Server;
}
