<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Response;

use Innmind\Rest\Client\{
    Response\ExtractIdentities,
    Identity as IdentityInterface,
    Visitor\ResolveIdentity,
    Definition\HttpResource,
    Definition\Identity,
    Definition\Property,
    Definition\AllowedLink,
};
use Innmind\Http\{
    Message\Response,
    Headers\Headers,
    Header\Value,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Url\Url;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set,
};
use PHPUnit\Framework\TestCase;

class ExtractIdentitiesTest extends TestCase
{
    private $extract;
    private $definition;

    public function setUp(): void
    {
        $this->extract = new ExtractIdentities(
            new ResolveIdentity(
                new UrlResolver
            )
        );
        $this->definition = new HttpResource(
            'foo',
            Url::fromString('http://example.com/foo'),
            new Identity('uuid'),
            new Map('string', Property::class),
            new Map('scalar', 'variable'),
            new Set(AllowedLink::class),
            false
        );
    }

    public function testDenormalizeWithoutLinks()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                Headers::of()
            );

        $identities = ($this->extract)($response, $this->definition);

        $this->assertInstanceOf(SetInterface::class, $identities);
        $this->assertSame(
            IdentityInterface::class,
            (string) $identities->type()
        );
        $this->assertCount(0, $identities);
    }

    public function testDenormalizeWithLinks()
    {
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                Headers::of(
                    new Link(
                        new LinkValue(
                            Url::fromString('/foo/42'),
                            'resource'
                        ),
                        new LinkValue(
                            Url::fromString('/foo/66'),
                            'resource'
                        ),
                        new LinkValue(
                            Url::fromString('/foo?range[]=10&range[]=20'),
                            'next'
                        )
                    )
                )
            );

        $identities = ($this->extract)($response, $this->definition);

        $this->assertInstanceOf(SetInterface::class, $identities);
        $this->assertSame(
            IdentityInterface::class,
            (string) $identities->type()
        );
        $this->assertCount(2, $identities);
        $this->assertSame('42', (string) $identities->current());
        $identities->next();
        $this->assertSame('66', (string) $identities->current());
    }
}
