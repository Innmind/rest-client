<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\Definition\{
    Type\IntType,
    Types,
    TypeInterface
};
use PHPUnit\Framework\TestCase;

class IntTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(TypeInterface::class, new IntType);
    }

    public function testFromString()
    {
        $type = IntType::fromString(
            'int',
            new Types
        );

        $this->assertInstanceOf(IntType::class, $type);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenBuildInvalidType()
    {
        IntType::fromString('float', new Types);
    }

    public function testNormalize()
    {
        $this->assertSame(1, (new IntType)->normalize(1));
        $this->assertSame(0, (new IntType)->normalize([]));
        $this->assertSame(1, (new IntType)->normalize(true));
        $this->assertSame(1, (new IntType)->normalize(1.2));
        $this->assertSame(1, (new IntType)->normalize('1.2'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\NormalizationException
     * @expectedExceptionMessage The value must be an integer
     */
    public function testThrowWhenNormalizingInvalidData()
    {
        (new IntType)->normalize(new \stdClass);
    }

    public function testDenormalize()
    {
        $this->assertSame(1, (new IntType)->denormalize(1));
        $this->assertSame(0, (new IntType)->denormalize([]));
        $this->assertSame(1, (new IntType)->denormalize(true));
        $this->assertSame(1, (new IntType)->denormalize(1.2));
        $this->assertSame(1, (new IntType)->denormalize('1.2'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DenormalizationException
     * @expectedExceptionMessage The value must be an integer
     */
    public function testThrowWhenDenormalizingInvalidData()
    {
        (new IntType)->denormalize(new \stdClass);
    }

    public function testCast()
    {
        $this->assertSame('int', (string) new IntType);
    }
}
