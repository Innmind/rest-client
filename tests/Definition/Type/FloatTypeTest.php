<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Type\FloatType,
    Definition\Types,
    Definition\Type,
    Exception\DomainException,
    Exception\NormalizationException,
    Exception\DenormalizationException,
};
use PHPUnit\Framework\TestCase;

class FloatTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new FloatType);
    }

    public function testFromString()
    {
        $type = FloatType::fromString(
            'float',
            new Types
        );

        $this->assertInstanceOf(FloatType::class, $type);
    }

    public function testThrowWhenBuildInvalidType()
    {
        $this->expectException(DomainException::class);

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

    public function testThrowWhenNormalizingInvalidData()
    {
        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage('The value must be a float');

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

    public function testThrowWhenDenormalizingInvalidData()
    {
        $this->expectException(DenormalizationException::class);
        $this->expectExceptionMessage('The value must be a float');

        (new FloatType)->denormalize(new \stdClass);
    }

    public function testCast()
    {
        $this->assertSame('float', (new FloatType)->toString());
    }
}
