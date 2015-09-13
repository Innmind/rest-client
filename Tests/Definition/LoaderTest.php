<?php

namespace Innmind\Rest\Client\Tests\Definition;

use Innmind\Rest\Client\Definition\Loader;
use Innmind\Rest\Client\Definition\Resource;
use Innmind\Rest\Client\Cache\InMemoryCache;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class LoaderTest extends \PHPUnit_Framework_TestCase
{
    protected $l;
    protected $c;

    public function setUp()
    {
        $this->l = new Loader(
            $this->c = new InMemoryCache,
            null,
            $http = $this
                ->getMockBuilder(Client::class)
                ->getMock()
        );

        $response = new Response(
            200,
            [
                'Link' => [
                    '</foo/bar/>; rel="property"; name="foo"; type="array"; access="READ|UPDATE"; variants="baz|bar"; optional="1"',
                    '<http://google.com>; rel="foo"',
                ],
            ],
            Stream::factory(json_encode([
                'resource' => [
                    'id' => 'uuid',
                    'properties' => [
                        'foobar' => [
                            'type' => 'string',
                            'access' => ['READ'],
                            'variants' => [],
                        ],
                    ],
                    'meta' => [
                        'desc' => 'foo',
                    ],
                ],
            ]))
        );
        $subResponse = new Response(
            200,
            [],
            Stream::factory(json_encode([
                'resource' => [
                    'id' => 'id',
                    'properties' => [
                        'id' => [
                            'type' => 'string',
                            'access' => ['READ'],
                            'variants' => [],
                        ],
                    ],
                ],
            ]))
        );

        $http
            ->method('options')
            ->will($this->onConsecutiveCalls($response, $subResponse));
    }

    public function testLoad()
    {
        $resource = $this->l->load('http://xn--example.com/foo');

        $this->assertInstanceOf(Resource::class, $resource);
        $this->assertSame(
            'uuid',
            $resource->getId()
        );
        $this->assertSame(
            ['desc' => 'foo'],
            $resource->getMetas()
        );
        $this->assertTrue($resource->hasProperty('foo'));
        $this->assertTrue($resource->hasProperty('foobar'));
        $this->assertSame(
            ['foobar', 'foo'],
            array_keys($resource->getProperties())
        );
        $foo = $resource->getProperty('foo');
        $this->assertSame(
            'array',
            $foo->getType()
        );
        $this->assertSame(
            'resource',
            $foo->getInnerType()
        );
        $this->assertSame(
            ['READ', 'UPDATE'],
            $foo->getAccess()
        );
        $this->assertSame(
            ['baz', 'bar'],
            $foo->getVariants()
        );
        $this->assertTrue($foo->isOptional());
        $sub = $foo->getResource();
        $this->assertInstanceOf(Resource::class, $sub);
        $this->assertSame(
            'id',
            $sub->getId()
        );
        $this->assertSame(
            'string',
            $sub->getProperty('id')->getType()
        );

        $this->assertSame(
            $resource,
            $this->l->load('http://xn--example.com/foo')
        );
        $this->assertTrue($this->c->has('http://xn--example.com/foo'));
    }
}
