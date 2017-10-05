<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\Definition\{
    Type\DateType,
    Types,
    Type
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

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenBuildInvalidType()
    {
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

    /**
     * @expectedException Innmind\Rest\Client\Exception\NormalizationException
     * @expectedExceptionMessage The value must be an instance of \DateTimeInterface
     */
    public function testThrowWhenNormalizingInvalidData()
    {
        (new DateType('c'))->normalize(new \stdClass);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\NormalizationException
     * @expectedExceptionMessage The value must be a date
     */
    public function testThrowWhenNormalizingInvalidDateString()
    {
        (new DateType('c'))->normalize('foo');
    }

    public function testDenormalize()
    {
        $date = new DateType('d/m/Y');

        $value = $date->denormalize('30/01/2016');
        $this->assertInstanceOf(\DateTimeImmutable::class, $value);
        $this->assertSame('2016-01-30', $value->format('Y-m-d'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DenormalizationException
     * @expectedExceptionMessage The value must be a string
     */
    public function testThrowWhenDenormalizingInvalidData()
    {
        (new DateType('c'))->denormalize(new \stdClass);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DenormalizationException
     * @expectedExceptionMessage The value must be a valid date
     */
    public function testThrowWhenDenormalizingInvalidDateString()
    {
        (new DateType('c'))->denormalize('foo');
    }

    public function testCast()
    {
        $this->assertSame('date<c>', (string) new DateType('c'));
    }
}
