<?php

namespace Innmind\Rest\Client\Tests\Server\Decoder;

use Innmind\Rest\Client\Server\Decoder\DelegationDecoder;
use Innmind\Rest\Client\Server\Decoder\ResourceDecoder;
use Innmind\Rest\Client\Server\Decoder\CollectionDecoder;
use Innmind\UrlResolver\UrlResolver;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class DelegationDecoderTest extends \PHPUnit_Framework_TestCase
{
    protected $b;

    public function setUp()
    {
        $resolver = new UrlResolver([]);
        $this->b = new DelegationDecoder([
            new ResourceDecoder($resolver),
            new CollectionDecoder($resolver),
        ]);
    }

    public function testSupports()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            Stream::factory(json_encode([
                'resource' => [
                    'foo' => 'bar',
                ],
            ]))
        );

        $this->assertTrue($this->b->supports($response));

        $response = new Response(
            200,
            ['Content-Type' => 'text/json'],
            Stream::factory(json_encode([
                'resource' => [
                    'foo' => 'foo',
                ],
            ]))
        );

        $this->assertFalse($this->b->supports($response));

        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            Stream::factory(json_encode([
                'foo' => 'foo',
            ]))
        );

        $this->assertFalse($this->b->supports($response));

        $response = new Response(
            200,
            ['Link' => '</foo/bar/>; rel="resource"']
        );

        $this->assertTrue($this->b->supports($response));

        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            Stream::factory(json_encode([
                'resources' => [[
                    'foo' => 'foo',
                ]],
            ]))
        );

        $this->assertFalse($this->b->supports($response));
    }

    public function testDecode()
    {
        $response = new Response(
            200,
            [
                'Link' => '</foo/bar/1>; rel="property"; name="coll", </foo/bar/2>; rel="property"; name="coll", </foo/bar/3>; rel="property"; name="sub"',
                'Content-Type' => 'application/json',
            ],
            Stream::factory(json_encode([
                'resource' => [
                    'foo' => 'foo',
                ],
            ]))
        );
        $response->setEffectiveUrl('http://xn--example.com/foo/bar/42');

        $this->assertSame(
            [
                'resource' => [
                    'properties' => [
                        'foo' => 'foo',
                    ],
                    'subResources' => [
                        'coll' => [
                            'http://xn--example.com/foo/bar/1',
                            'http://xn--example.com/foo/bar/2',
                        ],
                        'sub' => 'http://xn--example.com/foo/bar/3',
                    ],
                ],
            ],
            $this->b->decode($response)
        );

        $response = new Response(
            200,
            ['Link' => '</foo/bar/1>; rel="resource", </foo/bar/2>; rel="resource", </foo/bar/?offset=42>; rel="next", </foo/bar/>; rel="prev"']
        );
        $response->setEffectiveUrl('http://xn--example.com/foo/bar/?offset=24');

        $this->assertSame(
            [
                'resources' => [
                    'http://xn--example.com/foo/bar/1',
                    'http://xn--example.com/foo/bar/2',
                ],
                'next' => 'http://xn--example.com/foo/bar/?offset=42',
                'prev' => 'http://xn--example.com/foo/bar/',
            ],
            $this->b->decode($response)
        );
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage Trying to decode data out of an unsupported response
     */
    public function testThrowIfTryingToDecodeUnsupportedResponse()
    {
        $this->b->decode(new Response(200));
    }
}
