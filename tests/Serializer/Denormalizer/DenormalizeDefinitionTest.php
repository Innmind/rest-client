<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Denormalizer;

use Innmind\Rest\Client\{
    Serializer\Denormalizer\DenormalizeDefinition,
    Definition\HttpResource,
    Definition\Types,
};
use PHPUnit\Framework\TestCase;

class DenormalizeDefinitionTest extends TestCase
{
    private $denormalize;
    private $raw;

    public function setUp()
    {
        $types = new Types(...Types::defaults());

        $this->denormalize = new DenormalizeDefinition($types);
        $this->raw = [
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
                    'variants' => [],
                    'optional' => true,
                ],
            ],
            'metas' => [
                'foo' => ['bar' => 'baz'],
            ],
            'linkable_to' => [
                'rel' => 'res',
            ],
            'rangeable' => true,
        ];
    }

    public function testDenormalize()
    {
        $definition = ($this->denormalize)($this->raw, 'foo');

        $this->assertInstanceOf(HttpResource::class, $definition);
        $this->assertSame('foo', $definition->name());
        $this->assertSame('http://example.com/foo', (string) $definition->url());
        $this->assertSame('uuid', (string) $definition->identity());
        $this->assertSame(
            'uuid',
            $definition->properties()->get('uuid')->name()
        );
        $this->assertSame(
            'string',
            (string) $definition->properties()->get('uuid')->type()
        );
        $this->assertSame(
            ['READ'],
            $definition->properties()->get('uuid')->access()->mask()->toPrimitive()
        );
        $this->assertSame(
            ['guid'],
            $definition->properties()->get('uuid')->variants()->toPrimitive()
        );
        $this->assertFalse(
            $definition->properties()->get('uuid')->isOptional()
        );
        $this->assertSame(
            'url',
            $definition->properties()->get('url')->name()
        );
        $this->assertSame(
            'string',
            (string) $definition->properties()->get('url')->type()
        );
        $this->assertSame(
            ['READ', 'CREATE', 'UPDATE'],
            $definition->properties()->get('url')->access()->mask()->toPrimitive()
        );
        $this->assertSame(
            [],
            $definition->properties()->get('url')->variants()->toPrimitive()
        );
        $this->assertTrue(
            $definition->properties()->get('url')->isOptional()
        );
        $this->assertSame(
            ['bar' => 'baz'],
            $definition->metas()->get('foo')
        );
        $this->assertCount(1, $definition->links());
        $this->assertSame('res', $definition->links()->get('rel'));
        $this->assertTrue($definition->isRangeable());
    }
}
