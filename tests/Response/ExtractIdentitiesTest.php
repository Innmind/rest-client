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
    Headers,
    Header\Value,
    Header\Link,
    Header\LinkValue,
};
use Innmind\Url\Url;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Immutable\{
    Map,
    Set,
};
use function Innmind\Immutable\unwrap;
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
            Url::of('http://example.com/foo'),
            new Identity('uuid'),
            Map::of('string', Property::class),
            Map::of('scalar', 'variable'),
            Set::of(AllowedLink::class),
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

        $this->assertInstanceOf(Set::class, $identities);
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
                            Url::of('/foo/42'),
                            'resource'
                        ),
                        new LinkValue(
                            Url::of('/foo/66'),
                            'resource'
                        ),
                        new LinkValue(
                            Url::of('/foo?range[]=10&range[]=20'),
                            'next'
                        )
                    )
                )
            );

        $identities = ($this->extract)($response, $this->definition);

        $this->assertInstanceOf(Set::class, $identities);
        $this->assertSame(
            IdentityInterface::class,
            (string) $identities->type()
        );
        $this->assertCount(2, $identities);
        $identities = unwrap($identities);
        $this->assertSame('42', (string) \current($identities));
        \next($identities);
        $this->assertSame('66', (string) \current($identities));
    }
}
