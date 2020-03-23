<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Response;

use Innmind\Rest\Client\{
    Response\ExtractIdentity,
    Identity as IdentityInterface,
    Visitor\ResolveIdentity,
    Definition\HttpResource,
    Definition\Identity,
    Definition\Property,
    Definition\AllowedLink,
    Exception\IdentityNotFound,
};
use Innmind\Http\{
    Message\Response,
    Headers,
    Header\Header,
    Header\Value\Value,
    Header\HeaderValue,
    Header\Location,
    Header\LocationValue,
};
use Innmind\Url\Url;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Immutable\{
    Map,
    Set,
};
use PHPUnit\Framework\TestCase;

class ExtractIdentityTest extends TestCase
{
    private $extract;
    private $definition;

    public function setUp(): void
    {
        $this->extract = new ExtractIdentity(
            new ResolveIdentity(
                new UrlResolver
            )
        );
        $this->definition = new HttpResource(
            'foo',
            Url::of('http://example.com/foo'),
            new Identity('uuid'),
            Map::of('string', Property::class),
            Map::of('scalar', 'variable'),
            Set::of(AllowedLink::class),
            false
        );
    }

    public function testThrowWhenDenormalizingWithoutLocation()
    {
        $this->expectException(IdentityNotFound::class);

        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers
            );

        ($this->extract)($response, $this->definition);
    }

    public function testThrowWhenDenormalizingWithUnsupportedLocation()
    {
        $this->expectException(IdentityNotFound::class);

        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new Header(
                        'Location',
                        new Value('http://example.com/foo/42')
                    )
                )
            );

        ($this->extract)($response, $this->definition);
    }

    public function testDenormalize()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new Location(
                        new LocationValue(
                            Url::of('http://example.com/foo/42')
                        )
                    )
                )
            );

        $identity = ($this->extract)($response, $this->definition);

        $this->assertInstanceOf(IdentityInterface::class, $identity);
        $this->assertSame('42', $identity->toString());
    }
}
