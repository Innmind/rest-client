<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client;

use Innmind\Rest\Client\{
    Serializer,
    Serializer\Normalizer\CapabilitiesNamesNormalizer,
};
use Symfony\Component\Serializer\Serializer as SfSerializer;
use PHPUnit\Framework\TestCase;

class SerializerTest extends TestCase
{
    public function testBuild()
    {
        $serializer = Serializer::build(new CapabilitiesNamesNormalizer);

        $this->assertInstanceOf(SfSerializer::class, $serializer);
        $this->assertTrue($serializer->supportsDenormalization([], 'capabilities_names'));
        $this->assertTrue($serializer->supportsDecoding('json'));
    }
}
