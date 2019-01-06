<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\DefinitionNormalizer,
    Serializer\Denormalizer\DenormalizeDefinition,
    Definition\HttpResource,
    Definition\Types,
};
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use PHPUnit\Framework\TestCase;

class DefinitionNormalizerTest extends TestCase
{
    private $normalizer;
    private $raw;

    public function setUp()
    {
        $this->normalizer = new DefinitionNormalizer;
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
            NormalizerInterface::class,
            $this->normalizer
        );
    }

    public function testSupportsNormalization()
    {
        $this->assertTrue(
            $this->normalizer->supportsNormalization(
                (new DenormalizeDefinition(new Types))($this->raw, 'foo')
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
        $definition = (new DenormalizeDefinition(new Types))($this->raw, 'foo');

        $data = $this->normalizer->normalize($definition);

        $this->assertSame($this->raw, $data);
    }
}
