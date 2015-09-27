<?php

namespace Innmind\Rest\Client\Server;

use GuzzleHttp\Message\ResponseInterface;

interface DecoderInterface
{
    /**
     * Check if the decoder can decode something out of the given response
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    public function supports(ResponseInterface $response);

    /**
     * Decode the response content
     *
     * @param ResponseInterface $response
     *
     * @return mixed
     */
    public function decode(ResponseInterface $response);
}
