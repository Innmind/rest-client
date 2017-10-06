<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\Server as ServerInterface;
use Innmind\Url\UrlInterface;

final class RetryServerFactory implements Factory
{
    private $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function make(UrlInterface $url): ServerInterface
    {
        return new RetryServer(
            $this->factory->make($url)
        );
    }
}
