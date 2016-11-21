<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Normalizer;

use Innmind\Rest\Client\Serializer\Normalizer\CapabilitiesNamesNormalizer;
use Innmind\Immutable\SetInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CapabilitiesNamesNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            DenormalizerInterface::class,
            new CapabilitiesNamesNormalizer
        );
    }

    public function testSupportsDenormalization()
    {
        $normalizer = new CapabilitiesNamesNormalizer;
        $this->assertTrue(
            $normalizer->supportsDenormalization([], 'capabilities_names')
        );
        $this->assertFalse(
            $normalizer->supportsDenormalization('', 'capabilities_names')
        );
        $this->assertFalse(
            $normalizer->supportsDenormalization([], 'capabilities')
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingUnsupportedData()
    {
        (new CapabilitiesNamesNormalizer)->denormalize(
            '',
            'capabilities_names'
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\LogicException
     */
    public function testThrowWhenDenormalizingUnsupportedType()
    {
        (new CapabilitiesNamesNormalizer)->denormalize(
            [],
            'capabilities'
        );
    }

    public function testDenormalize()
    {
        $names = (new CapabilitiesNamesNormalizer)->denormalize(
            ['foo', 'bar'],
            'capabilities_names'
        );

        $this->assertInstanceOf(SetInterface::class, $names);
        $this->assertSame('string', (string) $names->type());
        $this->assertSame(['foo', 'bar'], $names->toPrimitive());
    }
}
