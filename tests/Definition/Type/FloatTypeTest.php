<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\Definition\{
    Type\FloatType,
    Types,
    TypeInterface
};

class FloatTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(TypeInterface::class, new FloatType);
    }

    public function testFromString()
    {
        $type = FloatType::fromString(
            'float',
            new Types
        );

        $this->assertInstanceOf(FloatType::class, $type);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenBuildInvalidType()
    {
        FloatType::fromString('int', new Types);
    }

    public function testNormalize()
    {
        $this->assertSame(1.0, (new FloatType)->normalize(1));
        $this->assertSame(0.0, (new FloatType)->normalize([]));
        $this->assertSame(1.0, (new FloatType)->normalize(true));
        $this->assertSame(1.2, (new FloatType)->normalize(1.2));
        $this->assertSame(1.2, (new FloatType)->normalize('1.2'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\NormalizationException
     * @expectedExceptionMessage The value must be a float
     */
    public function testThrowWhenNormalizingInvalidData()
    {
        (new FloatType)->normalize(new \stdClass);
    }

    public function testDenormalize()
    {
        $this->assertSame(1.0, (new FloatType)->denormalize(1));
        $this->assertSame(0.0, (new FloatType)->denormalize([]));
        $this->assertSame(1.0, (new FloatType)->denormalize(true));
        $this->assertSame(1.2, (new FloatType)->denormalize(1.2));
        $this->assertSame(1.2, (new FloatType)->denormalize('1.2'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DenormalizationException
     * @expectedExceptionMessage The value must be a float
     */
    public function testThrowWhenDenormalizingInvalidData()
    {
        (new FloatType)->denormalize(new \stdClass);
    }

    public function testCast()
    {
        $this->assertSame('float', (string) new FloatType);
    }
}
