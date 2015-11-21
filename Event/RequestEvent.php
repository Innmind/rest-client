<?php

namespace Innmind\Rest\Client\Event;

use Innmind\Rest\Client\Definition\ResourceDefinition;
use Symfony\Component\EventDispatcher\Event;
use GuzzleHttp\Message\RequestInterface;

class RequestEvent extends Event
{
    protected $request;
    protected $definition;

    public function __construct(
        RequestInterface $request,
        ResourceDefinition $definition
    ) {
        $this->request = $request;
        $this->definition = $definition;
    }

    /**
     * Return the request to be sent to the API
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Return the definition for the resource that is handled
     *
     * @return ResourceDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }
}
