<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Client;

use Innmind\Rest\Client\{
    Client as ClientInterface,
    Server,
    Server\Factory,
};
use Innmind\Url\Url;
use Innmind\Immutable\Map;

final class Client implements ClientInterface
{
    private $make;
    private $servers;

    public function __construct(Factory $make)
    {
        $this->make = $make;
        $this->servers = new Map('string', Server::class);
    }

    public function server(string $url): Server
    {
        $url = Url::fromString($url);
        $hash = \md5((string) $url);

        if ($this->servers->contains($hash)) {
            return $this->servers->get($hash);
        }

        $server = ($this->make)($url);
        $this->servers = $this->servers->put($hash, $server);

        return $server;
    }
}
