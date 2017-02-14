<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\Definition\{
    Type\SetType,
    Type\DateType,
    Types,
    TypeInterface
};
use Innmind\Immutable\{
    Set,
    SetInterface
};
use PHPUnit\Framework\TestCase;

class SetTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            new SetType(new DateType('c'))
        );
    }

    public function testFromString()
    {
        $type = SetType::fromString(
            'set<date<c>>',
            (new Types)->register(DateType::class)
        );

        $this->assertInstanceOf(SetType::class, $type);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenBuildInvalidType()
    {
        SetType::fromString('set<>', new Types);
    }

    public function testNormalize()
    {
        $date = new SetType(new DateType('d/m/Y'));

        $this->assertSame(
            ['30/01/2016'],
            $date->normalize(
                (new Set('string'))->add('2016-01-30')
            )
        );
        $this->assertSame(
            ['30/01/2016'],
            $date->normalize(
                (new Set(\DateTimeInterface::class))->add(
                    new \DateTime('2016-01-30')
                )
            )
        );
        $this->assertSame(
            ['30/01/2016'],
            $date->normalize(
                (new Set(\DateTimeInterface::class))->add(
                    new \DateTimeImmutable('2016-01-30')
                )
            )
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\NormalizationException
     * @expectedExceptionMessage The value must be an instance of Innmind\Immutable\SetInterface
     */
    public function testThrowWhenNormalizingInvalidData()
    {
        (new SetType(new DateType('c')))->normalize(new \stdClass);
    }

    public function testDenormalize()
    {
        $date = new SetType(new DateType('d/m/Y'));

        $value = $date->denormalize(['30/01/2016']);
        $this->assertInstanceOf(SetInterface::class, $value);
        $this->assertSame(\DateTimeImmutable::class, (string) $value->type());
        $this->assertSame('2016-01-30', $value->current()->format('Y-m-d'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DenormalizationException
     * @expectedExceptionMessage The value must be an array
     */
    public function testThrowWhenDenormalizingInvalidData()
    {
        (new SetType(new DateType('c')))->denormalize(new \stdClass);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DenormalizationException
     * @expectedExceptionMessage The value must be a valid set
     */
    public function testThrowWhenDenormalizingInvalidDateString()
    {
        (new SetType(new DateType('c')))->denormalize(['foo']);
    }

    public function testCast()
    {
        $this->assertSame(
            'set<date<c>>',
            (string) new SetType(new DateType('c'))
        );
    }
}
