<?php

namespace Innmind\Rest\Client\Server;

use Innmind\Rest\Client\Definition\Resource as Definition;
use Innmind\Rest\Client\Client;

class Collection implements CollectionInterface
{
    protected $definition;
    protected $loaded = [];
    protected $links;
    protected $next;
    protected $client;
    protected $index = 0;

    public function __construct(
        Definition $definition,
        array $links,
        $next,
        Client $client
    ) {
        $this->definition = $definition;
        $this->links = $links;
        $this->next = (string) $next;
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Check if a next page is available for this collection
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return !empty($this->next);
    }

    /**
     * Return the next page url
     *
     * @return string
     */
    public function getNextPage()
    {
        return $this->next;
    }

    /**
     * Return the links to resources that are not yet loaded inside the collection
     *
     * @return array
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if (isset($this->loaded[$this->index])) {
            return $this->loaded[$this->index];
        }

        $count = count($this->loaded);
        $index = $count - $this->index;

        if (!isset($this->links[$index])) {
            if ($this->hasNextPage()) {
                $this->loadNextPage();

                return $this->current();
            }

            return null;
        }

        $resource = $this->client->read($this->links[$index]);
        $this->loaded[] = $resource;
        unset($this->links[$index]);

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->index++;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        if (isset($this->loaded[$this->index])) {
            return true;
        }

        $index = count($this->loaded) - $this->index;

        if (isset($this->links[$index])) {
            return true;
        }

        return $this->hasNextPage();
    }

    /**
     * {@inheritdoc}
     *
     * Bear in mind the number returned only contains the resources the
     * collection is awared of (further pages not loaded yet are not included)
     */
    public function count()
    {
        return count($this->loaded) + count($this->links);
    }

    /**
     * Load the next page of the collection
     *
     * @return void
     */
    protected function loadNextPage()
    {
        $collection = $this->client->read($this->next);
        $this->links = array_merge(
            $this->links,
            $collection->getLinks()
        );
        $this->next = $collection->getNext();
    }
}
