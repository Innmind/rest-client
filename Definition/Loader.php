<?php

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\CacheInterface;
use Innmind\Rest\Client\Exception\DefinitionLoadException;
use Innmind\UrlResolver\ResolverInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ValidatorInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Request;

class Loader
{
    protected $cache;
    protected $builder;
    protected $validator;
    protected $http;
    protected $resolver;

    public function __construct(
        CacheInterface $cache,
        ResolverInterface $resolver,
        Builder $builder = null,
        Client $http = null,
        ValidatorInterface $validator = null
    ) {
        $this->cache = $cache;
        $this->builder = $builder ?: new Builder;
        $this->validator = $validator ?: Validation::createValidator();
        $this->http = $http ?: new Client;
        $this->resolver = $resolver;
    }

    /**
     * Load the resource definition for the given url
     *
     * @param string $url
     *
     * @return ResourceDefinition
     */
    public function load($url)
    {
        $url = $this->cleanUrl($url);

        if ($this->cache->has($url)) {
            return $this->cache->get($url);
        }

        return $this->refresh($url);
    }

    /**
     * Fetch the definition from the server
     *
     * @param string $url
     *
     * @return ResourceDefinition
     */
    public function refresh($url)
    {
        $url = $this->cleanUrl($url);

        $options = $this->http->options(
            (string) $url,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]
        );

        if ($options->getStatusCode() !== 200) {
            throw new DefinitionLoadException(sprintf(
                'No resource definition found at "%s"',
                $url
            ));
        }

        $definition = $options->json();

        if (!isset($definition['resource'])) {
            throw new DefinitionLoadException(sprintf(
                'The resource definition can\'t be found for the url "%s"',
                $url
            ));
        }

        $definition['resource']['url'] = $url;

        if ($options->hasHeader('Link')) {
            $links = Request::parseHeader($options, 'Link');

            foreach ($links as $link) {
                if ($link['rel'] !== 'property') {
                    continue;
                }

                $linkDef = [
                    'type' => $link['type'],
                    'access' => explode('|', $link['access']),
                    'variants' => explode('|', $link['variants']),
                    'optional' => (bool) (int) $link['optional'],
                ];

                if ($link['type'] === 'array') {
                    $linkDef['inner_type'] = 'resource';
                }

                $linkDef['resource'] = $this->refresh(
                    $this->resolver->resolve(
                        $url,
                        substr($link[0], 1, -1)
                    )
                );
                $definition['resource']['properties'][$link['name']] = $linkDef;
            }
        }

        $resource = $this->builder->build($definition['resource']);

        if (!$this->cache->has($url)) {
            $this->cache->save($url, $resource);
        } else {
            $resource = $this
                ->cache
                ->get($url)
                ->refresh(
                    $resource->getId(),
                    $resource->getProperties(),
                    $resource->getMetas()
                );
        }

        return $resource;
    }

    /**
     * Clean the url from any query parameter or fragment
     *
     * @param string $url
     *
     * @return string
     */
    protected function cleanUrl($url)
    {
        if ($this->resolver->isFolder($url)) {
            return $url;
        }

        return $this->resolver->folder($url);
    }
}
