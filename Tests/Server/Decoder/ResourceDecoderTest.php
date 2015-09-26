<?php

namespace Innmind\Rest\Client\Tests\Server\Decoder;

use Innmind\Rest\Client\Server\Decoder\ResourceDecoder;
use Innmind\UrlResolver\UrlResolver;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class ResourceDecoderTest extends \PHPUnit_Framework_TestCase
{
    protected $b;

    public function setUp()
    {
        $this->b = new ResourceDecoder(new UrlResolver([]));
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
    }
}
