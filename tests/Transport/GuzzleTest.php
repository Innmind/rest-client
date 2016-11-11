<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Transport;

use Innmind\Rest\Client\{
    Transport\Guzzle,
    TransportInterface
};
use Innmind\Url\Url;
use Innmind\Http\{
    Translator\Response\Psr7Translator,
    Factory\Header\DefaultFactory,
    Factory\HeaderFactoryInterface,
    Message\ResponseInterface,
    Message\Request,
    Message\Method,
    ProtocolVersion,
    Headers,
    Header\HeaderInterface,
    Header\ContentType,
    Header\ContentTypeValue,
    Header\ParameterInterface
};
use Innmind\Filesystem\Stream\StringStream;
use Innmind\Immutable\Map;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

class GuzzleTest extends \PHPUnit_Framework_TestCase
{
    public function testFulfill()
    {
        $transport = new Guzzle(
            $client = $this->createMock(ClientInterface::class),
            new Psr7Translator(
                new DefaultFactory(
                    new Map('string', HeaderFactoryInterface::class)
                )
            )
        );
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'http://example.com/',
                []
            )
            ->willReturn(
                $response = $this->createMock(Psr7ResponseInterface::class)
            );
        $response
            ->method('getProtocolVersion')
            ->willReturn('1.1');
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('getHeaders')
            ->willReturn([]);

        $response = $transport->fulfill(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
                new StringStream('')
            )
        );

        $this->assertInstanceOf(TransportInterface::class, $transport);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testFulfillWithMethod()
    {
        $transport = new Guzzle(
            $client = $this->createMock(ClientInterface::class),
            new Psr7Translator(
                new DefaultFactory(
                    new Map('string', HeaderFactoryInterface::class)
                )
            )
        );
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'http://example.com/',
                []
            )
            ->willReturn(
                $response = $this->createMock(Psr7ResponseInterface::class)
            );
        $response
            ->method('getProtocolVersion')
            ->willReturn('1.1');
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('getHeaders')
            ->willReturn([]);

        $response = $transport->fulfill(
            new Request(
                Url::fromString('http://example.com'),
                new Method('POST'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
                new StringStream('')
            )
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testFulfillWithHeaders()
    {
        $transport = new Guzzle(
            $client = $this->createMock(ClientInterface::class),
            new Psr7Translator(
                new DefaultFactory(
                    new Map('string', HeaderFactoryInterface::class)
                )
            )
        );
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'http://example.com/',
                [
                    'headers' => ['Content-Type' => ['application/json']],
                ]
            )
            ->willReturn(
                $response = $this->createMock(Psr7ResponseInterface::class)
            );
        $response
            ->method('getProtocolVersion')
            ->willReturn('1.1');
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('getHeaders')
            ->willReturn([]);

        $response = $transport->fulfill(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'Content-Type',
                            new ContentType(
                                new ContentTypeValue(
                                    'application',
                                    'json',
                                    new Map('string', ParameterInterface::class)
                                )
                            )
                        )
                ),
                new StringStream('')
            )
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testFulfillWithPayload()
    {
        $transport = new Guzzle(
            $client = $this->createMock(ClientInterface::class),
            new Psr7Translator(
                new DefaultFactory(
                    new Map('string', HeaderFactoryInterface::class)
                )
            )
        );
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'http://example.com/',
                [
                    'body' => 'content',
                ]
            )
            ->willReturn(
                $response = $this->createMock(Psr7ResponseInterface::class)
            );
        $response
            ->method('getProtocolVersion')
            ->willReturn('1.1');
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('getHeaders')
            ->willReturn([]);

        $response = $transport->fulfill(
            new Request(
                Url::fromString('http://example.com'),
                new Method('GET'),
                new ProtocolVersion(1, 1),
                new Headers(new Map('string', HeaderInterface::class)),
                new StringStream('content')
            )
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testFulfillCompletelyModifiedRequest()
    {
        $transport = new Guzzle(
            $client = $this->createMock(ClientInterface::class),
            new Psr7Translator(
                new DefaultFactory(
                    new Map('string', HeaderFactoryInterface::class)
                )
            )
        );
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'http://example.com/',
                [
                    'body' => 'content',
                    'headers' => ['Content-Type' => ['application/json']],
                ]
            )
            ->willReturn(
                $response = $this->createMock(Psr7ResponseInterface::class)
            );
        $response
            ->method('getProtocolVersion')
            ->willReturn('1.1');
        $response
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->method('getHeaders')
            ->willReturn([]);

        $response = $transport->fulfill(
            new Request(
                Url::fromString('http://example.com'),
                new Method('POST'),
                new ProtocolVersion(1, 1),
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'Content-Type',
                            new ContentType(
                                new ContentTypeValue(
                                    'application',
                                    'json',
                                    new Map('string', ParameterInterface::class)
                                )
                            )
                        )
                ),
                new StringStream('content')
            )
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
