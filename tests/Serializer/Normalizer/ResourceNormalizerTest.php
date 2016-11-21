<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\ResourceNormalizer,
    Serializer\Normalizer\DefinitionNormalizer,
    HttpResource,
    HttpResource\Property,
    Definition\HttpResource as ResourceDefinition,
    Definition\Access,
    Definition\Types
};
use Innmind\Immutable\{
    Set,
    Map
};
use Symfony\Component\Serializer\Normalizer\{
    DenormalizerInterface,
    NormalizerInterface
};

class ResourceNormalizerTest extends \PHPUnit_Framework_TestCase
{
    private $normalizer;
    private $definition;

    public function setUp()
    {
        $this->normalizer = new ResourceNormalizer;
        $types = new Types;
        Types::defaults()->foreach(function(string $class) use ($types) {
            $types->register($class);
        });
        $this->definition = (new DefinitionNormalizer($types))->denormalize(
            [
                'url' => 'http://example.com/foo',
                'identity' => 'uuid',
                'properties' => [
                    'uuid' => [
                        'type' => 'string',
                        'access' => ['READ'],
                        'variants' => ['guid'],
                        'optional' => false,
                    ],
                    'url' => [
                        'type' => 'string',
                        'access' => ['READ', 'CREATE', 'UPDATE'],
                        'variants' => ['uri'],
                        'optional' => true,
                    ],
                    'unwanted' => [
                        'type' => 'int',
                        'access' => ['UPDATE'],
                        'variants' => [],
                        'optional' => true,
                    ],
                    'onCreate' => [
                        'type' => 'int',
                        'access' => ['CREATE'],
                        'variants' => [],
                        'optional' => false,
                    ],
                ],
                'metas' => [
                    'foo' => ['bar' => 'baz'],
                ],
                'rangeable' => true,
            ],
            ResourceDefinition::class,
            null,
            ['name' => 'foo']
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            DenormalizerInterface::class,
            $this->normalizer
        );
        $this->assertInstanceOf(
            NormalizerInterface::class,
            $this->normalizer
        );
    }

    public function testSupportsDenormalization()
    {
        $this->assertTrue(
            $this->normalizer->supportsDenormalization(
                ['resource' => []],
                HttpResource::class
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                ['resource' => ''],
                HttpResource::class
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                [],
                HttpResource::class
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                '',
                HttpResource::class
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization(
                ['resource' => []],
                Unknown::class
            )
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingInvalidData()
    {
        $this->normalizer->denormalize(
            [],
            HttpResource::class,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingInvalidType()
    {
        $this->normalizer->denormalize(
            ['resource' => []],
            Unknown::class,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithoutDefinition()
    {
        $this->normalizer->denormalize(
            ['resource' => []],
            Unknown::class,
            null,
            [
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithInvalidDefinition()
    {
        $this->normalizer->denormalize(
            ['resource' => []],
            Unknown::class,
            null,
            [
                'definition' => [],
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithoutAccess()
    {
        $this->normalizer->denormalize(
            ['resource' => []],
            Unknown::class,
            null,
            [
                'definition' => $this->definition,
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithInvalidAccess()
    {
        $this->normalizer->denormalize(
            ['resource' => []],
            Unknown::class,
            null,
            [
                'definition' => $this->definition,
                'access' => 'READ',
            ]
        );
    }

    public function testDenormalize()
    {
        $resource = $this->normalizer->denormalize(
            [
                'resource' => [
                    'uuid' => 'some uuid',
                    'url' => 'http://example.com',
                    'unwanted' => 42,
                ],
            ],
            HttpResource::class,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );

        $this->assertInstanceOf(HttpResource::class, $resource);
        $this->assertSame('foo', $resource->name());
        $this->assertCount(2, $resource->properties());
        $this->assertSame(
            'some uuid',
            $resource->properties()->get('uuid')->value()
        );
        $this->assertSame(
            'http://example.com',
            $resource->properties()->get('url')->value()
        );
    }

    public function testDenormalizeWithOptionalProperty()
    {
        $resource = $this->normalizer->denormalize(
            [
                'resource' => [
                    'uuid' => 'some uuid',
                ],
            ],
            HttpResource::class,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );

        $this->assertInstanceOf(HttpResource::class, $resource);
        $this->assertSame('foo', $resource->name());
        $this->assertCount(1, $resource->properties());
        $this->assertSame(
            'some uuid',
            $resource->properties()->get('uuid')->value()
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\MissingPropertyException
     * @expectedExceptionMessage Missing property "uuid"
     */
    public function testThrowWhenDenormalizingWithMissingProperty()
    {
        $this->normalizer->denormalize(
            [
                'resource' => [],
            ],
            HttpResource::class,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('READ')
                ),
            ]
        );
    }

    public function testSupportsNormalization()
    {
        $this->assertTrue(
            $this->normalizer->supportsNormalization(
                new HttpResource(
                    'foo',
                    new Map('string', Property::class)
                )
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsNormalization(
                new \stdClass
            )
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenNormalizingInvalidData()
    {
        $this->normalizer->normalize(
            new \stdClass,
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('CREATE')
                )
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenNormalizingWithoutDefinition()
    {
        $this->normalizer->normalize(
            new HttpResource(
                'foo',
                new Map('string', Property::class)
            ),
            null,
            [
                'access' => new Access(
                    (new Set('string'))->add('CREATE')
                )
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenNormalizingWithInvalidDefinition()
    {
        $this->normalizer->normalize(
            new HttpResource(
                'foo',
                new Map('string', Property::class)
            ),
            null,
            [
                'definition' => [],
                'access' => new Access(
                    (new Set('string'))->add('CREATE')
                )
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenNormalizingWithoutAccess()
    {
        $this->normalizer->normalize(
            new HttpResource(
                'foo',
                new Map('string', Property::class)
            ),
            null,
            [
                'definition' => $this->definition,
            ]
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenNormalizingWithInvalidAccess()
    {
        $this->normalizer->normalize(
            new HttpResource(
                'foo',
                new Map('string', Property::class)
            ),
            null,
            [
                'definition' => $this->definition,
                'access' => ['CREATE'],
            ]
        );
    }

    public function testNormalize()
    {
        $resource = $this->normalizer->normalize(
            new HttpResource(
                'foo',
                (new Map('string', Property::class))
                    ->put(
                        'url',
                        new Property(
                            'url',
                            'http://example.com/'
                        )
                    )
                    ->put(
                        'onCreate',
                        new Property(
                            'onCreate',
                            '42'
                        )
                    )
            ),
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('CREATE')
                )
            ]
        );

        $this->assertSame(
            [
                'resource' => [
                    'url' => 'http://example.com/',
                    'onCreate' => 42,
                ],
            ],
            $resource
        );
    }

    public function testNormalizeWithVariant()
    {
        $resource = $this->normalizer->normalize(
            new HttpResource(
                'foo',
                (new Map('string', Property::class))
                    ->put(
                        'uri',
                        new Property(
                            'uri',
                            'http://example.com/'
                        )
                    )
                    ->put(
                        'onCreate',
                        new Property(
                            'onCreate',
                            '42'
                        )
                    )
            ),
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('CREATE')
                )
            ]
        );

        $this->assertSame(
            [
                'resource' => [
                    'url' => 'http://example.com/',
                    'onCreate' => 42,
                ],
            ],
            $resource
        );
    }

    public function testNormalizeWithOptionalProperty()
    {
        $resource = $this->normalizer->normalize(
            new HttpResource(
                'foo',
                (new Map('string', Property::class))
                    ->put(
                        'onCreate',
                        new Property(
                            'onCreate',
                            '42'
                        )
                    )
            ),
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('CREATE')
                )
            ]
        );

        $this->assertSame(
            ['resource' => ['onCreate' => 42]],
            $resource
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\MissingPropertyException
     * @expectedExceptionMessage Missing property "onCreate"
     */
    public function testThrowWhenNormalizingWithMissingProperty()
    {
        $this->normalizer->normalize(
            new HttpResource(
                'foo',
                new Map('string', Property::class)
            ),
            null,
            [
                'definition' => $this->definition,
                'access' => new Access(
                    (new Set('string'))->add('CREATE')
                )
            ]
        );
    }
}
