<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client;

use Innmind\Http\Message\{
    RequestInterface,
    ResponseInterface
};

interface TransportInterface
{
    public function fulfill(RequestInterface $request): ResponseInterface;
}
