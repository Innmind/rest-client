<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\Definition\{
    Type\StringType,
    Types,
    TypeInterface
};

class StringTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(TypeInterface::class, new StringType);
    }

    public function testFromString()
    {
        $type = StringType::fromString(
            'string',
            new Types
        );

        $this->assertInstanceOf(StringType::class, $type);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenBuildInvalidType()
    {
        StringType::fromString('int', new Types);
    }

    public function testNormalize()
    {
        $this->assertSame('1', (new StringType)->normalize(1));
        $this->assertSame('1', (new StringType)->normalize(true));
        $this->assertSame('', (new StringType)->normalize(false));
        $this->assertSame('1.2', (new StringType)->normalize(1.2));
        $this->assertSame('1.2', (new StringType)->normalize('1.2'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\NormalizationException
     * @expectedExceptionMessage The value must be a string
     */
    public function testThrowWhenNormalizingInvalidData()
    {
        (new StringType)->normalize(new \stdClass);
    }

    public function testDenormalize()
    {
        $this->assertSame('1', (new StringType)->denormalize(1));
        $this->assertSame('1', (new StringType)->denormalize(true));
        $this->assertSame('', (new StringType)->denormalize(false));
        $this->assertSame('1.2', (new StringType)->denormalize(1.2));
        $this->assertSame('1.2', (new StringType)->denormalize('1.2'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DenormalizationException
     * @expectedExceptionMessage The value must be a string
     */
    public function testThrowWhenDenormalizingInvalidData()
    {
        (new StringType)->denormalize(new \stdClass);
    }

    public function testCast()
    {
        $this->assertSame('string', (string) new StringType);
    }
}
