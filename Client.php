<?php

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\Definition\Loader;
use Innmind\Rest\Client\Definition\Resource as Definition;
use Innmind\Rest\Client\Exception\ResourceCreationException;
use Innmind\Rest\Client\Exception\ResourceUpdateException;
use Innmind\Rest\Client\Exception\ResourceDeletionException;
use Innmind\Rest\Client\Exception\ValidationException;
use Innmind\Rest\Client\Server\CollectionInterface;
use Innmind\Rest\Client\Server\Resource as ServerResource;
use Innmind\UrlResolver\ResolverInterface;
use GuzzleHttp\Client as Http;
use Symfony\Component\Serializer\SerializerInterface;

class Client
{
    protected $loader;
    protected $serializer;
    protected $resolver;
    protected $validator;
    protected $http;

    public function __construct(
        Loader $loader,
        SerializerInterface $serializer,
        ResolverInterface $resolver,
        Validator $validator,
        Http $http = null
    ) {
        $this->loader = $loader;
        $this->serializer = $serializer;
        $this->resolver = $resolver;
        $this->validator = $validator;
        $this->http = $http ?: new Http;
    }

    /**
     * Fetch all the resources found at the given url
     *
     * @param string $url
     *
     * @return Server\CollectionInterface|Server\Resource
     */
    public function read($url)
    {
        $definition = $this->loader->load($url);

        $response = $this->http->get($url, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if ($this->resolver->isFolder($url)) {
            $type = CollectionInterface::class;
        } else {
            $type = ServerResource::class;
        }

        $data = $this->serializer->deserialize(
            $response,
            $type,
            'rest_response',
            [
                'client' => $this,
                'definition' => $definition,
            ]
        );

        return $data;
    }

    /**
     * Create a resource at the given url
     *
     * @param string $url
     * @param Resource $resource
     *
     * @throws ResourceCreationException If the resource creation fails
     *
     * @return Client self
     */
    public function create($url, Resource $resource)
    {
        $definition = $this->loader->load($url);

        try {
            $json = $this->serializer->serialize($resource, 'json', [
                'definition' => $definition,
                'action' => Action::CREATE,
            ]);
            $response = $this->http->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => $json,
            ]);

            if ($response->getStatusCode() !== 201) {
                throw new ResourceCreationException;
            }
        } catch (ResourceCreationException $e) {
            if ($definition->isFresh()) {
                throw $e;
            }

            $this->loader->refresh($url);
            $this->validate($resource, $definition, Action::CREATE);
            $this->create($url, $resource);
        }

        return $this;
    }

    /**
     * Update the resource at the given url
     *
     * @param string $url
     * @param Resource $resource
     *
     * @throws ResourceUpdateException If the update fails
     *
     * @return Client self
     */
    public function update($url, Resource $resource)
    {
        $definition = $this->loader->load($url);

        try {
            $json = $this->serializer->serialize($resource, 'json', [
                'definition' => $definition,
                'action' => Action::UPDATE,
            ]);
            $response = $this->http->put($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => $json,
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new ResourceUpdateException;
            }
        } catch (ResourceUpdateException $e) {
            if ($definition->isFresh()) {
                throw $e;
            }

            $this->loader->refresh($url);
            $this->validate($resource, $definition, Action::UPDATE);
            $this->update($url, $resource);
        }

        return $this;
    }

    /**
     * Delete the resource found at the given url
     *
     * @param string $url
     *
     * @throws ResourceDeletionException If the resource deletion fails
     *
     * @return Client self
     */
    public function remove($url)
    {
        $response = $this->http->delete($url);

        if ($response->getStatusCode() !== 204) {
            throw new ResourceDeletionException;
        }

        return $this;
    }

    /**
     * Validate the given resource against the given definition
     *
     * @param Resource $resource
     * @param Definition $definition
     * @param string $action
     *
     * @throws ValidationException If the resource doesn't comply with its definition
     *
     * @return void
     */
    protected function validate(
        Resource $resource,
        Definition $definition,
        $action
    ) {
        $violations = $this->validator->validate(
            $resource,
            $definition,
            $action
        );

        if ($violations->count() > 0) {
            throw new ValidationException((string) $violations);
        }
    }
}
