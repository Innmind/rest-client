<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\IdentitiesNormalizer,
    IdentityInterface
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
use Innmind\Immutable\{
    Map,
    Set,
    SetInterface
};
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class IdentitiesNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            DenormalizerInterface::class,
            new IdentitiesNormalizer
        );
    }

    public function testSupportsDenormalization()
    {
        $normalizer = new IdentitiesNormalizer;

        $this->assertTrue(
            $normalizer->supportsDenormalization(
                $this->createMock(ResponseInterface::class),
                'rest_identities'
            )
        );
        $this->assertFalse(
            $normalizer->supportsDenormalization(
                new \stdClass,
                'rest_identities'
            )
        );
        $this->assertFalse(
            $normalizer->supportsDenormalization(
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
        (new IdentitiesNormalizer)->denormalize(
            new \stdClass,
            'rest_identities'
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingInvalidType()
    {
        (new IdentitiesNormalizer)->denormalize(
            $this->createMock(ResponseInterface::class),
            'identities'
        );
    }

    public function testDenormalizeWithoutLinks()
    {
        $normalizer = new IdentitiesNormalizer;
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('headers')
            ->willReturn(
                new Headers(
                    new Map('string', HeaderInterface::class)
                )
            );

        $identities = $normalizer->denormalize($response, 'rest_identities');

        $this->assertInstanceOf(SetInterface::class, $identities);
        $this->assertSame(
            IdentityInterface::class,
            (string) $identities->type()
        );
        $this->assertCount(0, $identities);
    }

    public function testDenormalizeWithLinks()
    {
        $normalizer = new IdentitiesNormalizer;
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

        $identities = $normalizer->denormalize($response, 'rest_identities');

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
