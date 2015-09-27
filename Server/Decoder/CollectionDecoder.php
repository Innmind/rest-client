<?php

namespace Innmind\Rest\Client\Server\Decoder;

use Innmind\Rest\Client\Server\DecoderInterface;
use Innmind\UrlResolver\ResolverInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Message\Response;

class CollectionDecoder implements DecoderInterface
{
    const REL = 'resource';

    protected $resolver;

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ResponseInterface $response)
    {
        $links = Response::parseHeader($response, 'Link');

        foreach ($links as $link) {
            if (
                isset($link['rel']) &&
                $link['rel'] === self::REL
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function decode(ResponseInterface $response)
    {
        $links = Response::parseHeader($response, 'Link');
        $resources = [];
        $next = null;
        $prev = null;

        foreach ($links as $link) {
            if (isset($link['rel'])) {
                $url = $this->resolver->resolve(
                    $response->getEffectiveUrl(),
                    substr($link[0], 1, -1)
                );

                switch ($link['rel']) {
                    case self::REL:
                        $resources[] = $url;
                        break;
                    case 'next':
                        $next = $url;
                        break;
                    case 'prev':
                        $prev = $url;
                        break;
                }
            }
        }

        return [
            'resources' => $resources,
            'next' => $next,
            'prev' => $prev,
        ];
    }
}
