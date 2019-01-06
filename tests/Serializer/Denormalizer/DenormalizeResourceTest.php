<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Denormalizer;

use Innmind\Rest\Client\{
    Serializer\Denormalizer\DenormalizeResource,
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

class DenormalizeResourceTest extends TestCase
{
    private $denormalize;
    private $definition;

    public function setUp()
    {
        $this->denormalize = new DenormalizeResource;
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

    public function testDenormalize()
    {
        $resource = ($this->denormalize)(
            [
                'resource' => [
                    'uuid' => 'some uuid',
                    'url' => 'http://example.com',
                    'unwanted' => 42,
                ],
            ],
            $this->definition,
            new Access('READ')
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
        $resource = ($this->denormalize)(
            [
                'resource' => [
                    'uuid' => 'some uuid',
                ],
            ],
            $this->definition,
            new Access('READ')
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
     * @expectedException Innmind\Rest\Client\Exception\MissingProperty
     * @expectedExceptionMessage Missing property "uuid"
     */
    public function testThrowWhenDenormalizingWithMissingProperty()
    {
        ($this->denormalize)(
            [
                'resource' => [],
            ],
            $this->definition,
            new Access('READ')
        );
    }
}
