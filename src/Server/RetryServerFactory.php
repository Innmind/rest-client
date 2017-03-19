<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\ServerInterface;
use Innmind\Url\UrlInterface;

final class RetryServerFactory implements FactoryInterface
{
    private $factory;

    public function __construct(FactoryInterface $factory)
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
