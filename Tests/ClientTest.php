<?php

namespace Innmind\Rest\Client\Tests;

use Innmind\Rest\Client\Client;
use Innmind\Rest\Client\Validator;
use Innmind\Rest\Client\Resource;
use Innmind\Rest\Client\Serializer\Normalizer\CollectionNormalizer;
use Innmind\Rest\Client\Serializer\Normalizer\ResourceNormalizer;
use Innmind\Rest\Client\Serializer\Encoder\ResponseEncoder;
use Innmind\Rest\Client\Server\Collection;
use Innmind\Rest\Client\Server\Resource as ServerResource;
use Innmind\Rest\Client\Server\Decoder\DelegationDecoder;
use Innmind\Rest\Client\Server\Decoder\CollectionDecoder;
use Innmind\Rest\Client\Server\Decoder\ResourceDecoder;
use Innmind\Rest\Client\Definition\Loader;
use Innmind\Rest\Client\Cache\InMemoryCache;
use Innmind\Rest\Client\Exception\ResourceCreationException;
use Innmind\Rest\Client\Exception\ResourceUpdateException;
use Innmind\UrlResolver\UrlResolver;
use GuzzleHttp\Client as Http;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Validator\Validation;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    protected $c;
    protected $l;
    protected $cache;

    public function setUp()
    {
        $resolver = new UrlResolver([]);
        $http = $this
            ->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['send', 'options'])
            ->getMock();

        $this->c = new Client(
            $this->l = new Loader(
                $this->cache = new InMemoryCache,
                $resolver,
                null,
                $http
            ),
            new Serializer(
                [new CollectionNormalizer, new ResourceNormalizer],
                [
                    new ResponseEncoder(new DelegationDecoder([
                        new CollectionDecoder($resolver),
                        new ResourceDecoder($resolver),
                    ])),
                    new JsonEncoder,
                ]
            ),
            $resolver,
            new Validator(Validation::createValidator(), new ResourceNormalizer),
            new EventDispatcher,
            $http
        );

        $http
            ->method('send')
            ->will($this->returnCallback(function($request) {
                $url = $request->getUrl();

                switch ($url) {
                    case 'http://xn--example.com/read/':
                        $response = new Response(
                            200,
                            ['Link' => '</read/42>; rel="resource"']
                        );
                        break;
                    case 'http://xn--example.com/read/42':
                        $response = new Response(
                            200,
                            ['Content-Type' => 'application/json'],
                            Stream::factory(json_encode([
                                'resource' => [
                                    'foo' => 'bar'
                                ],
                            ]))
                        );
                        break;
                    case 'http://xn--example.com/create/':
                        $response = new Response(201);
                        break;
                    case 'http://xn--example.com/create/fail/':
                        $response = new Response(400);
                        break;
                    case 'http://xn--example.com/update/42':
                        $response = new Response(200);
                        break;
                    case 'http://xn--example.com/update/fail/42':
                        $response = new Response(400);
                        break;
                    case 'http://xn--example.com/delete/42':
                        $response = new Response(204);
                        break;
                    case 'http://xn--example.com/delete/fail/42':
                        $response = new Response(400);
                        break;
                }

                $response->setEffectiveUrl($url);

                return $response;
            }));
        $http
            ->method('options')
            ->will($this->returnCallback(function($url) {
                $response = new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    Stream::factory(json_encode([
                        'resource' => [
                            'id' => 'id',
                            'properties' => [
                                'foo' => [
                                    'type' => 'string',
                                    'access' => ['READ', 'CREATE', 'UPDATE'],
                                    'variants' => [],
                                ],
                            ],
                        ],
                    ]))
                );
                $response->setEffectiveUrl($url);

                return $response;
            }));
    }

    public function testRead()
    {
        $response = $this->c->read('http://xn--example.com/read/');
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertSame(1, $response->count());

        $response = $this->c->read('http://xn--example.com/read/42');
        $this->assertInstanceOf(ServerResource::class, $response);
        $this->assertSame('bar', $response->get('foo'));
    }

    public function testCreate()
    {
        $r = new Resource;
        $r->set('foo', 'bar');
        $this->assertSame($this->c, $this->c->create(
            'http://xn--example.com/create/',
            $r
        ));
    }

    public function testRefreshDefinitionOnCreationFailure()
    {
        $url = 'http://xn--example.com/create/fail/';

        try {
            $r = new Resource;
            $r->set('foo', 'bar');
            $def = $this->l->load($url);
            $refl = new \ReflectionObject($def);
            $refl = $refl->getProperty('isFresh');
            $refl->setAccessible(true);
            $refl->setValue($def, false);
            $refl->setAccessible(false);
            $this->assertSame($this->c, $this->c->create(
                $url,
                $r
            ));
            $this->fail('It should throw an exception');
        } catch (ResourceCreationException $e) {
            $this->assertTrue($this->cache->get($url)->isFresh());
        }
    }

    public function testUpdate()
    {
        $r = new Resource;
        $r->set('foo', 'bar');
        $this->assertSame($this->c, $this->c->update(
            'http://xn--example.com/update/42',
            $r
        ));
    }

    public function testRefreshDefinitionOnUpdateFailure()
    {
        try {
            $r = new Resource;
            $r->set('foo', 'bar');
            $url = 'http://xn--example.com/update/fail/42';
            $def = $this->l->load($url);
            $refl = new \ReflectionObject($def);
            $refl = $refl->getProperty('isFresh');
            $refl->setAccessible(true);
            $refl->setValue($def, false);
            $refl->setAccessible(false);
            $this->assertSame($this->c, $this->c->update(
                $url,
                $r
            ));
            $this->fail('It should throw an exception');
        } catch (ResourceUpdateException $e) {
            $this->assertTrue($this->cache->get('http://xn--example.com/update/fail/')->isFresh());
        }
    }

    public function testRemove()
    {
        $this->assertSame(
            $this->c,
            $this->c->remove('http://xn--example.com/delete/42')
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\ResourceDeletionException
     */
    public function testThrowIfFailsToDeleteResource()
    {
        $this->c->remove('http://xn--example.com/delete/fail/42');
    }
}
