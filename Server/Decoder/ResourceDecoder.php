<?php

namespace Innmind\Rest\Client\Server\Decoder;

use Innmind\Rest\Client\Server\DecoderInterface;
use Innmind\UrlResolver\ResolverInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Message\Response;

class ResourceDecoder implements DecoderInterface
{
    const REL = 'property';

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
        if ($response->getHeader('Content-Type') !== 'application/json') {
            return false;
        }

        $content = $response->json();

        return isset($content['resource']);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(ResponseInterface $response)
    {
        $links = Response::parseHeader($response, 'Link');
        $subResources = [];

        foreach ($links as $link) {
            if (isset($link['rel']) && $link['rel'] === self::REL) {
                $url = $this->resolver->resolve(
                    $response->getEffectiveUrl(),
                    substr($link[0], 1, -1)
                );
                $name = $link['name'];

                if (isset($subResources[$name])) {
                    if (is_string($subResources[$name])) {
                        $subResources[$name] = [$subResources[$name], $url];
                    } else {
                        $subResources[$name][] = $url;
                    }
                } else {
                    $subResources[$name] = $url;
                }
            }
        }

        $content = $response->json();

        return [
            'resource' => [
                'properties' => $content['resource'],
                'subResources' => $subResources,
            ],
        ];
    }
}
