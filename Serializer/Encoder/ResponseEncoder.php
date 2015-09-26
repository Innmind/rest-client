<?php

namespace Innmind\Rest\Client\Serializer\Encoder;

use Innmind\Rest\Client\Server\DecoderInterface as ResponseDecoderInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use GuzzleHttp\Message\ResponseInterface;

class ResponseEncoder implements DecoderInterface
{
    protected $decoder;

    public function __construct(ResponseDecoderInterface $decoder)
    {
        $this->decoder = $decoder;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = [])
    {
        if (!$data instanceof ResponseInterface) {
            throw new InvalidArgumentException;
        }

        if (!$this->decoder->supports($data)) {
            throw new UnsupportedException(
                'The server response can\'t be decoded (no decoder found)'
            );
        }

        return $this->decoder->decode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return $format === 'rest_response';
    }
}
