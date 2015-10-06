<?php

namespace Innmind\Rest\Client\Exception;

use GuzzleHttp\Message\ResponseInterface;

abstract class AbstractResourceException extends \Exception
{
    protected $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Return the response
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}
