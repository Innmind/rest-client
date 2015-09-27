<?php

namespace Innmind\Rest\Client\Tests\Server\Decoder;

use Innmind\Rest\Client\Server\Decoder\CollectionDecoder;
use Innmind\UrlResolver\UrlResolver;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class CollectionDecoderTest extends \PHPUnit_Framework_TestCase
{
    protected $b;

    public function setUp()
    {
        $this->b = new CollectionDecoder(new UrlResolver([]));
    }

    public function testSupports()
    {
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
}
