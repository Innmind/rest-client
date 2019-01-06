<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\Server as ServerInterface;
use Innmind\Url\UrlInterface;

final class RetryServerFactory implements Factory
{
    private $make;

    public function __construct(Factory $make)
    {
        $this->make = $make;
    }

    public function __invoke(UrlInterface $url): ServerInterface
    {
        return new RetryServer(
            ($this->make)($url)
        );
    }
}
