<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Type\StringType,
    Definition\Types,
    Definition\Type,
    Exception\DomainException,
    Exception\NormalizationException,
    Exception\DenormalizationException,
};
use PHPUnit\Framework\TestCase;

class StringTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new StringType);
    }

    public function testFromString()
    {
        $type = StringType::fromString(
            'string',
            new Types
        );

        $this->assertInstanceOf(StringType::class, $type);
    }

    public function testThrowWhenBuildInvalidType()
    {
        $this->expectException(DomainException::class);

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

    public function testThrowWhenNormalizingInvalidData()
    {
        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage('The value must be a string');

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

    public function testThrowWhenDenormalizingInvalidData()
    {
        $this->expectException(DenormalizationException::class);
        $this->expectExceptionMessage('The value must be a string');

        (new StringType)->denormalize(new \stdClass);
    }

    public function testCast()
    {
        $this->assertSame('string', (string) new StringType);
    }
}
