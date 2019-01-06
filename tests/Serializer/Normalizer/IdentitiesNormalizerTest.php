<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\IdentitiesNormalizer,
    Identity as IdentityInterface,
    Visitor\ResolveIdentity,
    Definition\HttpResource,
    Definition\Identity,
    Definition\Property,
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
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use PHPUnit\Framework\TestCase;

class IdentitiesNormalizerTest extends TestCase
{
    private $normalizer;
    private $definition;

    public function setUp()
    {
        $this->normalizer = new IdentitiesNormalizer(
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
            new Map('string', 'string'),
            false
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            DenormalizerInterface::class,
            $this->normalizer
        );
    }

    public function testSupportsDenormalization()
    {
        $this->assertTrue(
            $this->normalizer->supportsDenormalization(
                $this->createMock(Response::class),
                'rest_identities'
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                new \stdClass,
                'rest_identities'
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                $this->createMock(Response::class),
                'identities'
            )
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingInvalidData()
    {
        $this->normalizer->denormalize(
            new \stdClass,
            'rest_identities',
            null,
            ['definition' => $this->definition]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingInvalidType()
    {
        $this->normalizer->denormalize(
            $this->createMock(Response::class),
            'identities',
            null,
            ['definition' => $this->definition]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithoutDefinition()
    {
        $this->normalizer->denormalize(
            $this->createMock(Response::class),
            'identities'
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithInvalidDefinition()
    {
        $this->normalizer->denormalize(
            $this->createMock(Response::class),
            'identities',
            null,
            ['definition' => []]
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

        $identities = $this->normalizer->denormalize(
            $response,
            'rest_identities',
            null,
            ['definition' => $this->definition]
        );

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

        $identities = $this->normalizer->denormalize(
            $response,
            'rest_identities',
            null,
            ['definition' => $this->definition]
        );

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
