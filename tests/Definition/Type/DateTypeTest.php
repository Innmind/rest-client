<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Type\DateType,
    Definition\Types,
    Definition\Type,
    Exception\DomainException,
    Exception\NormalizationException,
    Exception\DenormalizationException,
};
use PHPUnit\Framework\TestCase;

class DateTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Type::class, new DateType('c'));
    }

    public function testFromString()
    {
        $type = DateType::fromString(
            'date<c>',
            new Types
        );

        $this->assertInstanceOf(DateType::class, $type);
    }

    public function testThrowWhenBuildInvalidType()
    {
        $this->expectException(DomainException::class);

        DateType::fromString('date<>', new Types);
    }

    public function testNormalize()
    {
        $date = new DateType('d/m/Y');

        $this->assertSame('30/01/2016', $date->normalize('2016-01-30'));
        $this->assertSame(
            '30/01/2016',
            $date->normalize(new \DateTime('2016-01-30'))
        );
        $this->assertSame(
            '30/01/2016',
            $date->normalize(new \DateTimeImmutable('2016-01-30'))
        );
    }

    public function testThrowWhenNormalizingInvalidData()
    {
        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage('The value must be an instance of \DateTimeInterface');

        (new DateType('c'))->normalize(new \stdClass);
    }

    public function testThrowWhenNormalizingInvalidDateString()
    {
        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage('The value must be a date');

        (new DateType('c'))->normalize('foo');
    }

    public function testDenormalize()
    {
        $date = new DateType('d/m/Y');

        $value = $date->denormalize('30/01/2016');
        $this->assertInstanceOf(\DateTimeImmutable::class, $value);
        $this->assertSame('2016-01-30', $value->format('Y-m-d'));
    }

    public function testThrowWhenDenormalizingInvalidData()
    {
        $this->expectException(DenormalizationException::class);
        $this->expectExceptionMessage('The value must be a string');

        (new DateType('c'))->denormalize(new \stdClass);
    }

    public function testThrowWhenDenormalizingInvalidDateString()
    {
        $this->expectException(DenormalizationException::class);
        $this->expectExceptionMessage('The value must be a valid date');

        (new DateType('c'))->denormalize('foo');
    }

    public function testCast()
    {
        $this->assertSame('date<c>', (new DateType('c'))->toString());
    }
}
