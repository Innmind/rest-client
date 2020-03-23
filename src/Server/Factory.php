<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\Server as ServerInterface;
use Innmind\Url\Url;

interface Factory
{
    public function __invoke(Url $url): ServerInterface;
}
