<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\NormalizeResource,
    Serializer\Denormalizer\DenormalizeDefinition,
    HttpResource,
    HttpResource\Property,
    Definition\HttpResource as ResourceDefinition,
    Definition\Access,
    Definition\Types,
};
use Innmind\Immutable\{
    Set,
    Map,
};
use PHPUnit\Framework\TestCase;

class NormalizeResourceTest extends TestCase
{
    private $normalize;
    private $definition;

    public function setUp()
    {
        $this->normalize = new NormalizeResource;
        $this->definition = (new DenormalizeDefinition(new Types))(
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
                'linkable_to' => [],
                'rangeable' => true,
            ],
            'foo'
        );
    }

    public function testNormalize()
    {
        $resource = ($this->normalize)(
            HttpResource::of(
                'foo',
                new Property('url', 'http://example.com/'),
                new Property('onCreate', '42')
            ),
            $this->definition,
            new Access('CREATE')
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
        $resource = ($this->normalize)(
            HttpResource::of(
                'foo',
                new Property('uri', 'http://example.com/'),
                new Property('onCreate', '42')
            ),
            $this->definition,
            new Access('CREATE')
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
        $resource = ($this->normalize)(
            HttpResource::of(
                'foo',
                new Property('onCreate', '42')
            ),
            $this->definition,
            new Access('CREATE')
        );

        $this->assertSame(
            ['resource' => ['onCreate' => 42]],
            $resource
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\MissingProperty
     * @expectedExceptionMessage Missing property "onCreate"
     */
    public function testThrowWhenNormalizingWithMissingProperty()
    {
        ($this->normalize)(
            HttpResource::of('foo'),
            $this->definition,
            new Access('CREATE')
        );
    }
}
