<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Type\IntType,
    Definition\Types,
    Definition\Type,
    Exception\DomainException,
    Exception\NormalizationException,
    Exception\DenormalizationException,
};
use PHPUnit\Framework\TestCase;

class IntTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new IntType);
    }

    public function testFromString()
    {
        $type = IntType::fromString(
            'int',
            new Types
        );

        $this->assertInstanceOf(IntType::class, $type);
    }

    public function testThrowWhenBuildInvalidType()
    {
        $this->expectException(DomainException::class);

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

    public function testThrowWhenNormalizingInvalidData()
    {
        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage('The value must be an integer');

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

    public function testThrowWhenDenormalizingInvalidData()
    {
        $this->expectException(DenormalizationException::class);
        $this->expectExceptionMessage('The value must be an integer');

        (new IntType)->denormalize(new \stdClass);
    }

    public function testCast()
    {
        $this->assertSame('int', (new IntType)->toString());
    }
}
