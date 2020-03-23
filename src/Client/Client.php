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
    private Factory $make;
    /** @var Map<string, Server> */
    private Map $servers;

    public function __construct(Factory $make)
    {
        $this->make = $make;
        /** @var Map<string, Server> */
        $this->servers = Map::of('string', Server::class);
    }

    public function server(string $url): Server
    {
        $url = Url::of($url);
        $hash = \md5($url->toString());

        if ($this->servers->contains($hash)) {
            return $this->servers->get($hash);
        }

        $server = ($this->make)($url);
        $this->servers = ($this->servers)($hash, $server);

        return $server;
    }
}
