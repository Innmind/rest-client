<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Denormalizer;

use Innmind\Rest\Client\{
    Serializer\Denormalizer\DenormalizeDefinition,
    Definition\HttpResource,
    Definition\Types,
};
use function Innmind\Immutable\{
    unwrap,
    first,
};
use PHPUnit\Framework\TestCase;

class DenormalizeDefinitionTest extends TestCase
{
    private $denormalize;
    private $raw;

    public function setUp(): void
    {
        $types = new Types(...unwrap(Types::defaults()));

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
                [
                    'relationship' => 'rel',
                    'resource_path' => 'res',
                    'parameters' => [],
                ],
            ],
            'rangeable' => true,
        ];
    }

    public function testDenormalize()
    {
        $definition = ($this->denormalize)($this->raw, 'foo');

        $this->assertInstanceOf(HttpResource::class, $definition);
        $this->assertSame('foo', $definition->name());
        $this->assertSame('http://example.com/foo', $definition->url()->toString());
        $this->assertSame('uuid', $definition->identity()->toString());
        $this->assertSame(
            'uuid',
            $definition->properties()->get('uuid')->name()
        );
        $this->assertSame(
            'string',
            $definition->properties()->get('uuid')->type()->toString()
        );
        $this->assertSame(
            ['READ'],
            unwrap($definition->properties()->get('uuid')->access()->mask())
        );
        $this->assertSame(
            ['guid'],
            unwrap($definition->properties()->get('uuid')->variants())
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
            $definition->properties()->get('url')->type()->toString()
        );
        $this->assertSame(
            ['READ', 'CREATE', 'UPDATE'],
            unwrap($definition->properties()->get('url')->access()->mask())
        );
        $this->assertSame(
            [],
            unwrap($definition->properties()->get('url')->variants())
        );
        $this->assertTrue(
            $definition->properties()->get('url')->isOptional()
        );
        $this->assertSame(
            ['bar' => 'baz'],
            $definition->metas()->get('foo')
        );
        $this->assertCount(1, $definition->links());
        $this->assertSame('res', first($definition->links())->resourcePath());
        $this->assertSame('rel', first($definition->links())->relationship());
        $this->assertTrue($definition->isRangeable());
    }
}
