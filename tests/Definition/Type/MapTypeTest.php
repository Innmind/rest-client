<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition\Type;

use Innmind\Rest\Client\Definition\{
    Type\MapType,
    Type\DateType,
    Type\IntType,
    Types,
    TypeInterface
};
use Innmind\Immutable\{
    Map,
    MapInterface
};

class MapTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            new MapType(
                new IntType,
                new DateType('c')
            )
        );
    }

    public function testFromString()
    {
        $types = (new Types)
            ->register(DateType::class)
            ->register(IntType::class);
        $type = MapType::fromString(
            'map<int,date<c>>',
            $types
        );

        $this->assertInstanceOf(MapType::class, $type);

        $type = MapType::fromString(
            'map<int, date<c>>',
            $types
        );

        $this->assertInstanceOf(MapType::class, $type);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenBuildInvalidType()
    {
        MapType::fromString('map<,>', new Types);
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
                (new Map('string', 'string'))->put('2', '2016-01-30')
            )
        );
        $this->assertSame(
            [2 => '30/01/2016'],
            $date->normalize(
                (new Map('string', \DateTimeInterface::class))->put(
                    '2',
                    new \DateTime('2016-01-30')
                )
            )
        );
        $this->assertSame(
            [2 => '30/01/2016'],
            $date->normalize(
                (new Map('string', \DateTimeInterface::class))->put(
                    '2',
                    new \DateTimeImmutable('2016-01-30')
                )
            )
        );
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\NormalizationException
     * @expectedExceptionMessage The value must be an instance of Innmind\Immutable\MapInterface
     */
    public function testThrowWhenNormalizingInvalidData()
    {
        (new MapType(new IntType, new DateType('c')))->normalize(new \stdClass);
    }

    public function testDenormalize()
    {
        $date = new MapType(new IntType, new DateType('d/m/Y'));

        $value = $date->denormalize(['2' => '30/01/2016']);
        $this->assertInstanceOf(MapInterface::class, $value);
        $this->assertSame('int', (string) $value->keyType());
        $this->assertSame(\DateTimeImmutable::class, (string) $value->valueType());
        $this->assertCount(1, $value);
        $this->assertSame('2016-01-30', $value->get(2)->format('Y-m-d'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DenormalizationException
     * @expectedExceptionMessage The value must be an array
     */
    public function testThrowWhenDenormalizingInvalidData()
    {
        (new MapType(new IntType, new DateType('c')))->denormalize(new \stdClass);
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DenormalizationException
     * @expectedExceptionMessage The value must be a valid map
     */
    public function testThrowWhenDenormalizingInvalidDateString()
    {
        $values = new \SplObjectStorage;
        $values->attach(new \stdClass, 'foo');

        (new MapType(new IntType, new DateType('c')))->denormalize($values);
    }

    public function testCast()
    {
        $this->assertSame(
            'map<int, date<c>>',
            (string) new MapType(new IntType, new DateType('c'))
        );
    }
}
