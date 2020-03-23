<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\Server as ServerInterface;
use Innmind\Url\Url;

final class RetryServerFactory implements Factory
{
    private Factory $make;

    public function __construct(Factory $make)
    {
        $this->make = $make;
    }

    public function __invoke(Url $url): ServerInterface
    {
        return new RetryServer(
            ($this->make)($url)
        );
    }
}
