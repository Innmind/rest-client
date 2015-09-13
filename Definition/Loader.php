<?php

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\CacheInterface;
use Innmind\Rest\Client\Exception\DefinitionLoadException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Request;
use Pdp\PublicSuffixListManager;
use Pdp\Parser;

class Loader
{
    protected $cache;
    protected $builder;
    protected $validator;
    protected $http;
    protected $urlParser;

    public function __construct(
        CacheInterface $cache,
        Builder $builder = null,
        Client $http = null,
        ValidatorInterface $validator = null
    ) {
        $this->cache = $cache;
        $this->builder = $builder ?: new Builder;
        $this->validator = $validator ?: Validation::createValidator();
        $this->http = $http ?:  new Client;
        $this->urlParser = new Parser(
            (new PublicSuffixListManager)->getList()
        );
    }

    /**
     * Load the resource definition for the given url
     *
     * @param string $url
     *
     * @return Innmind\Rest\Client\Definition\Resource
     */
    public function load($url)
    {
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
     * @return Innmind\Rest\Client\Definition\Resource
     */
    public function refresh($url)
    {
        $options = $this->http->options((string) $url);

        $definition = $options->json();

        if (!isset($definition['resource'])) {
            throw new DefinitionLoadException(sprintf(
                'The resource definition can\'t be found for the url "%s"',
                $url
            ));
        }

        if ($options->hasHeader('Link')) {
            $links = $options->getHeaderAsArray('Link');
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
                    $this->buildUrl($link[0], $url)
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
     * Normalize the url found in a link
     *
     * @param string $link
     * @param string $url Original url where the link was found
     *
     * @return string
     */
    protected function buildUrl($link, $url)
    {
        $link = substr($link, 1, -1);

        if ($this->validator->validate($link, new Url)->count() === 0) {
            return $link;
        }

        $parsedUrl = $this->urlParser->parseUrl($url);
        $host = (string) $parsedUrl->host;

        return sprintf(
            '%s/%s',
            rtrim($host, '/'),
            ltrim($link, '/')
        );
    }
}
