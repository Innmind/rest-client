<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\ServerInterface;
use Innmind\Url\UrlInterface;

interface FactoryInterface
{
    public function make(UrlInterface $url): ServerInterface;
}
