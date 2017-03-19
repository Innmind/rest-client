<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\DefinitionNormalizer,
    Definition\Types,
    Definition\HttpResource
};
use Symfony\Component\Serializer\Normalizer\{
    DenormalizerInterface,
    NormalizerInterface
};
use PHPUnit\Framework\TestCase;

class DefinitionNormalizerTest extends TestCase
{
    private $normalizer;
    private $raw;

    public function setUp()
    {
        $types = new Types;
        Types::defaults()->foreach(function(string $class) use ($types) {
            $types->register($class);
        });

        $this->normalizer = new DefinitionNormalizer($types);
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
            $this->normalizer->supportsDenormalization([], HttpResource::class)
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization('', HttpResource::class)
        );
        $this->assertFalse(
            $this->normalizer->supportsDenormalization([], Unknown::class)
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingUnsupportedData()
    {
        $this->normalizer->denormalize(
            '',
            HttpResource::class,
            null,
            ['name' => 'foo']
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingUnsupportedClass()
    {
        $this->normalizer->denormalize(
            [],
            Unknown::class,
            null,
            ['name' => 'foo']
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingWithoutName()
    {
        $this->normalizer->denormalize(
            [],
            HttpResource::class
        );
    }

    public function testDenormalize()
    {
        $definition = $this->normalizer->denormalize(
            $this->raw,
            HttpResource::class,
            null,
            ['name' => 'foo']
        );

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

    public function testSupportsNormalization()
    {
        $this->assertTrue(
            $this->normalizer->supportsNormalization(
                $this->normalizer->denormalize(
                    $this->raw,
                    HttpResource::class,
                    null,
                    ['name' => 'foo']
                )
            )
        );
        $this->assertFalse(
            $this->normalizer->supportsNormalization([])
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenNormalizingInvalidData()
    {
        $this->normalizer->normalize([]);
    }

    public function testNormalize()
    {
        $definition = $this->normalizer->denormalize(
            $this->raw,
            HttpResource::class,
            null,
            ['name' => 'foo']
        );

        $data = $this->normalizer->normalize($definition);

        $this->assertSame($this->raw, $data);
    }
}
