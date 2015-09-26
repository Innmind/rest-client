<?php

namespace Innmind\Rest\Client\Tests\Serializer\Encoder;

use Innmind\Rest\Client\Serializer\Encoder\ResponseEncoder;
use Innmind\Rest\Client\Server\Decoder\CollectionDecoder;
use Innmind\UrlResolver\UrlResolver;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class ResponseEncoderTest extends \PHPUnit_Framework_TestCase
{
    protected $e;

    public function setUp()
    {
        $this->e = new ResponseEncoder(
            new CollectionDecoder(
                new UrlResolver([])
            )
        );
    }

    public function testSupportsDecoding()
    {
        $this->assertTrue($this->e->supportsDecoding('rest_response'));
        $this->assertFalse($this->e->supportsDecoding('response'));
        $this->assertFalse($this->e->supportsDecoding('rest'));
    }

    /**
     * @expectedException Symfony\Component\Serializer\Exception\InvalidArgumentException
     */
    public function testThrowIfNotTryingToDecodeResponse()
    {
        $this->e->decode('', 'rest_response');
    }

    /**
     * @expectedException Symfony\Component\Serializer\Exception\UnsupportedException
     * @expectedExceptionMessage The server response can't be decoded (no decoder found)
     */
    public function testThrowIfNoDecoderFound()
    {
        $this->e->decode(
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                Stream::factory(json_encode([
                    'resource' => [
                        'foo' => 'foo',
                    ],
                ]))
            ),
            'rest_response'
        );
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
            $this->e->decode($response, 'rest_response')
        );
    }
}
