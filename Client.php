<?php

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\Definition\Loader;
use Innmind\Rest\Client\Definition\ResourceDefinition as Definition;
use Innmind\Rest\Client\Exception\ResourceCreationException;
use Innmind\Rest\Client\Exception\ResourceUpdateException;
use Innmind\Rest\Client\Exception\ResourceDeletionException;
use Innmind\Rest\Client\Exception\ValidationException;
use Innmind\Rest\Client\Server\CollectionInterface;
use Innmind\Rest\Client\Server\HttpResourceInterface as ServerResourceInterface;
use Innmind\Rest\Client\Event\RequestEvent;
use Innmind\UrlResolver\ResolverInterface;
use GuzzleHttp\Client as Http;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Stream\Stream;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Client
{
    protected $loader;
    protected $serializer;
    protected $resolver;
    protected $validator;
    protected $dispatcher;
    protected $http;

    public function __construct(
        Loader $loader,
        SerializerInterface $serializer,
        ResolverInterface $resolver,
        Validator $validator,
        EventDispatcherInterface $dispatcher,
        Http $http = null
    ) {
        $this->loader = $loader;
        $this->serializer = $serializer;
        $this->resolver = $resolver;
        $this->validator = $validator;
        $this->dispatcher = $dispatcher;
        $this->http = $http ?: new Http;
    }

    /**
     * Fetch all the resources found at the given url
     *
     * @param string $url
     *
     * @return CollectionInterface|ServerResourceInterface
     */
    public function read($url)
    {
        $definition = $this->loader->load($url);
        $request = new Request('GET', $url, ['Accept' => 'application/json']);

        $this->dispatcher->dispatch(
            Events::REQUEST,
            new RequestEvent($request, $definition)
        );

        $response = $this->http->send($request);

        if ($this->resolver->isFolder($url)) {
            $type = CollectionInterface::class;
        } else {
            $type = ServerResourceInterface::class;
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
     * @param HttpResourceInterface $resource
     *
     * @throws ResourceCreationException If the resource creation fails
     *
     * @return Client self
     */
    public function create($url, HttpResourceInterface $resource)
    {
        $definition = $this->loader->load($url);

        try {
            $request = new Request(
                'POST',
                $url,
                [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                Stream::factory($this->serializer->serialize(
                    $resource,
                    'json',
                    [
                        'definition' => $definition,
                        'action' => Action::CREATE,
                    ])
                )
            );

            $this->dispatcher->dispatch(
                Events::REQUEST,
                new RequestEvent($request, $definition)
            );

            $response = $this->http->send($request);

            if ($response->getStatusCode() !== 201) {
                throw new ResourceCreationException($response);
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
     * @param HttpResourceInterface $resource
     *
     * @throws ResourceUpdateException If the update fails
     *
     * @return Client self
     */
    public function update($url, HttpResourceInterface $resource)
    {
        $definition = $this->loader->load($url);

        try {
            $request = new Request(
                'PUT',
                $url,
                [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                Stream::factory($this->serializer->serialize(
                    $resource,
                    'json',
                    [
                        'definition' => $definition,
                        'action' => Action::UPDATE,
                    ])
                )
            );

            $this->dispatcher->dispatch(
                Events::REQUEST,
                new RequestEvent($request, $definition)
            );

            $response = $this->http->send($request);

            if ($response->getStatusCode() !== 200) {
                throw new ResourceUpdateException($response);
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
        $definition = $this->loader->load($url);
        $request = new Request('DELETE', $url);

        $this->dispatcher->dispatch(
            Events::REQUEST,
            new RequestEvent($request, $definition)
        );

        $response = $this->http->send($request);

        if ($response->getStatusCode() !== 204) {
            throw new ResourceDeletionException($response);
        }

        return $this;
    }

    /**
     * Validate the given resource against the given definition
     *
     * @param HttpResourceInterface $resource
     * @param Definition $definition
     * @param string $action
     *
     * @throws ValidationException If the resource doesn't comply with its definition
     *
     * @return void
     */
    protected function validate(
        HttpResourceInterface $resource,
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
