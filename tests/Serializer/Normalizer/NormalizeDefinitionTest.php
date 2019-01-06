<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\{
    Serializer\Normalizer\NormalizeDefinition,
    Serializer\Denormalizer\DenormalizeDefinition,
    Definition\HttpResource,
    Definition\Types,
};
use PHPUnit\Framework\TestCase;

class NormalizeDefinitionTest extends TestCase
{
    private $normalize;
    private $raw;

    public function setUp()
    {
        $this->normalize = new NormalizeDefinition;
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

    public function testNormalize()
    {
        $definition = (new DenormalizeDefinition(new Types))($this->raw, 'foo');

        $data = ($this->normalize)($definition);

        $this->assertSame($this->raw, $data);
    }
}
