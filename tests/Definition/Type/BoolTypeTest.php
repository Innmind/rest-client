<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\Definition\{
    Type\BoolType,
    Types,
    TypeInterface
};

class BoolTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(TypeInterface::class, new BoolType);
    }

    public function testFromString()
    {
        $type = BoolType::fromString(
            'bool',
            new Types
        );

        $this->assertInstanceOf(BoolType::class, $type);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenBuildInvalidType()
    {
        BoolType::fromString('int', new Types);
    }

    public function testNormalize()
    {
        $this->assertTrue((new BoolType)->normalize(1));
        $this->assertTrue((new BoolType)->normalize(true));
        $this->assertTrue((new BoolType)->normalize('true'));
        $this->assertTrue((new BoolType)->normalize('false'));
        $this->assertFalse((new BoolType)->normalize(0));
        $this->assertFalse((new BoolType)->normalize(''));
        $this->assertFalse((new BoolType)->normalize([]));
    }

    public function testDenormalize()
    {
        $this->assertTrue((new BoolType)->denormalize(1));
        $this->assertTrue((new BoolType)->denormalize(true));
        $this->assertTrue((new BoolType)->denormalize('true'));
        $this->assertTrue((new BoolType)->denormalize('false'));
        $this->assertFalse((new BoolType)->denormalize(0));
        $this->assertFalse((new BoolType)->denormalize(''));
        $this->assertFalse((new BoolType)->denormalize([]));
    }

    public function testCast()
    {
        $this->assertSame('bool', (string) new BoolType);
    }
}
