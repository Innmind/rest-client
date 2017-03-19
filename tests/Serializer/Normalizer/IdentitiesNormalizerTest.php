<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\IdentitiesNormalizer,
    IdentityInterface,
    Visitor\ResolveIdentity,
    Definition\HttpResource,
    Definition\Identity,
    Definition\Property
};
use Innmind\Http\{
    Message\ResponseInterface,
    Headers,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\Link,
    Header\LinkValue,
    Header\ParameterInterface
};
use Innmind\Url\Url;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Immutable\{
    Map,
    Set,
    SetInterface
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
                $this->createMock(ResponseInterface::class),
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
                $this->createMock(ResponseInterface::class),
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
            $this->createMock(ResponseInterface::class),
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
            $this->createMock(ResponseInterface::class),
            'identities'
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithInvalidDefinition()
    {
        $this->normalizer->denormalize(
            $this->createMock(ResponseInterface::class),
            'identities',
            null,
            ['definition' => []]
        );
    }

    public function testDenormalizeWithoutLinks()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    new Map('string', HeaderInterface::class)
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
        $this->assertCount(0, $identities);
    }

    public function testDenormalizeWithLinks()
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'Link',
                            new Link(
                                (new Set(HeaderValueInterface::class))
                                    ->add(
                                        new LinkValue(
                                            Url::fromString('/foo/42'),
                                            'resource',
                                            new Map('string', ParameterInterface::class)
                                        )
                                    )
                                    ->add(
                                        new LinkValue(
                                            Url::fromString('/foo/66'),
                                            'resource',
                                            new Map('string', ParameterInterface::class)
                                        )
                                    )
                                    ->add(
                                        new LinkValue(
                                            Url::fromString('/foo?range[]=10&range[]=20'),
                                            'next',
                                            new Map('string', ParameterInterface::class)
                                        )
                                    )
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
