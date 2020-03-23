<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Type\SetType,
    Definition\Type\DateType,
    Definition\Types,
    Definition\Type,
    Exception\DomainException,
    Exception\NormalizationException,
    Exception\DenormalizationException,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\first;
use PHPUnit\Framework\TestCase;

class SetTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            new SetType(new DateType('c'))
        );
    }

    public function testFromString()
    {
        $type = SetType::of(
            'set<date<c>>',
            new Types(DateType::class)
        );

        $this->assertInstanceOf(SetType::class, $type);
    }

    public function testThrowWhenBuildInvalidType()
    {
        $this->expectException(DomainException::class);

        SetType::of('set<>', new Types);
    }

    public function testNormalize()
    {
        $date = new SetType(new DateType('d/m/Y'));

        $this->assertSame(
            ['30/01/2016'],
            $date->normalize(
                Set::of('string', '2016-01-30')
            )
        );
        $this->assertSame(
            ['30/01/2016'],
            $date->normalize(
                Set::of(\DateTimeInterface::class, new \DateTime('2016-01-30'))
            )
        );
        $this->assertSame(
            ['30/01/2016'],
            $date->normalize(
                Set::of(\DateTimeInterface::class, new \DateTimeImmutable('2016-01-30'))
            )
        );
    }

    public function testThrowWhenNormalizingInvalidData()
    {
        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage('The value must be an instance of Innmind\Immutable\Set');

        (new SetType(new DateType('c')))->normalize(new \stdClass);
    }

    public function testDenormalize()
    {
        $date = new SetType(new DateType('d/m/Y'));

        $value = $date->denormalize(['30/01/2016']);
        $this->assertInstanceOf(Set::class, $value);
        $this->assertSame(\DateTimeImmutable::class, (string) $value->type());
        $this->assertSame('2016-01-30', first($value)->format('Y-m-d'));
    }

    public function testThrowWhenDenormalizingInvalidData()
    {
        $this->expectException(DenormalizationException::class);
        $this->expectExceptionMessage('The value must be an array');

        (new SetType(new DateType('c')))->denormalize(new \stdClass);
    }

    public function testThrowWhenDenormalizingInvalidDateString()
    {
        $this->expectException(DenormalizationException::class);
        $this->expectExceptionMessage('The value must be a valid set');

        (new SetType(new DateType('c')))->denormalize(['foo']);
    }

    public function testCast()
    {
        $this->assertSame(
            'set<date<c>>',
            (new SetType(new DateType('c')))->toString()
        );
    }
}
