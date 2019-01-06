<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\Definition\{
    Type\BoolType,
    Types,
    Type,
};
use PHPUnit\Framework\TestCase;

class BoolTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new BoolType);
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
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenBuildInvalidType()
    {
        BoolType::fromString('int', new Types);
    }

    public function testNormalize()
    {
        $this->assertTrue((new BoolType)->normalize(1));
        $this->assertTrue((new BoolType)->normalize(1.1));
        $this->assertTrue((new BoolType)->normalize(true));
        $this->assertTrue((new BoolType)->normalize('true'));
        $this->assertTrue((new BoolType)->normalize('false'));
        $this->assertTrue((new BoolType)->normalize([0]));
        $this->assertTrue((new BoolType)->normalize(new \stdClass));
        $this->assertFalse((new BoolType)->normalize(0));
        $this->assertFalse((new BoolType)->normalize(''));
        $this->assertFalse((new BoolType)->normalize([]));
        $this->assertFalse((new BoolType)->normalize(false));
    }

    public function testDenormalize()
    {
        $this->assertTrue((new BoolType)->denormalize(1));
        $this->assertTrue((new BoolType)->denormalize(1.1));
        $this->assertTrue((new BoolType)->denormalize(true));
        $this->assertTrue((new BoolType)->denormalize('true'));
        $this->assertTrue((new BoolType)->denormalize('false'));
        $this->assertTrue((new BoolType)->denormalize([0]));
        $this->assertTrue((new BoolType)->denormalize(new \stdClass));
        $this->assertFalse((new BoolType)->denormalize(0));
        $this->assertFalse((new BoolType)->denormalize(''));
        $this->assertFalse((new BoolType)->denormalize([]));
        $this->assertFalse((new BoolType)->denormalize(false));
    }

    public function testCast()
    {
        $this->assertSame('bool', (string) new BoolType);
    }
}
