<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\{
    Definition\Type\MapType,
    Definition\Type\DateType,
    Definition\Type\IntType,
    Definition\Types,
    Definition\Type,
    Exception\DomainException,
    Exception\NormalizationException,
    Exception\DenormalizationException,
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class MapTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Type::class,
            new MapType(
                new IntType,
                new DateType('c')
            )
        );
    }

    public function testFromString()
    {
        $types = new Types(DateType::class, IntType::class);
        $type = MapType::of(
            'map<int,date<c>>',
            $types
        );

        $this->assertInstanceOf(MapType::class, $type);

        $type = MapType::of(
            'map<int, date<c>>',
            $types
        );

        $this->assertInstanceOf(MapType::class, $type);
    }

    public function testThrowWhenBuildInvalidType()
    {
        $this->expectException(DomainException::class);

        MapType::of('map<,>', new Types);
    }

    public function testNormalize()
    {
        $date = new MapType(
            new IntType,
            new DateType('d/m/Y')
        );

        $this->assertSame(
            [2 => '30/01/2016'],
            $date->normalize(
                Map::of('string', 'string')
                    ('2', '2016-01-30')
            )
        );
        $this->assertSame(
            [2 => '30/01/2016'],
            $date->normalize(
                Map::of('string', \DateTimeInterface::class)
                    ('2', new \DateTimeImmutable('2016-01-30'))
            )
        );
        $this->assertSame(
            [2 => '30/01/2016'],
            $date->normalize(
                Map::of('string', \DateTimeInterface::class)
                    ('2', new \DateTimeImmutable('2016-01-30'))
            )
        );
    }

    public function testThrowWhenNormalizingInvalidData()
    {
        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage('The value must be an instance of Innmind\Immutable\Map');

        (new MapType(new IntType, new DateType('c')))->normalize(new \stdClass);
    }

    public function testDenormalize()
    {
        $date = new MapType(new IntType, new DateType('d/m/Y'));

        $value = $date->denormalize(['2' => '30/01/2016']);
        $this->assertInstanceOf(Map::class, $value);
        $this->assertSame('int', (string) $value->keyType());
        $this->assertSame(\DateTimeImmutable::class, (string) $value->valueType());
        $this->assertCount(1, $value);
        $this->assertSame('2016-01-30', $value->get(2)->format('Y-m-d'));
    }

    public function testThrowWhenDenormalizingInvalidData()
    {
        $this->expectException(DenormalizationException::class);
        $this->expectExceptionMessage('The value must be an array');

        (new MapType(new IntType, new DateType('c')))->denormalize(new \stdClass);
    }

    public function testThrowWhenDenormalizingInvalidDateString()
    {
        $values = new \SplObjectStorage;
        $values->attach(new \stdClass, 'foo');

        $this->expectException(DenormalizationException::class);
        $this->expectExceptionMessage('The value must be a valid map');

        (new MapType(new IntType, new DateType('c')))->denormalize($values);
    }

    public function testCast()
    {
        $this->assertSame(
            'map<int, date<c>>',
            (new MapType(new IntType, new DateType('c')))->toString()
        );
    }
}
