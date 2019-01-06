<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\{
    Definition\Types,
    Definition\Type,
    Definition\Type\BoolType,
    Definition\Type\DateType,
    Definition\Type\FloatType,
    Definition\Type\IntType,
    Definition\Type\MapType,
    Definition\Type\SetType,
    Definition\Type\StringType,
    Exception\DomainException,
};
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testRegister()
    {
        $types = new Types;
        $object = $this->createMock(Type::class);

        $this->assertSame($types, $types->register(get_class($object)));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\DomainException
     */
    public function testThrowWhenRegisteringInvalidType()
    {
        (new Types)->register('stdClass');
    }

    public function testBuild()
    {
        $type1 = new class implements Type {
            public static function fromString(string $type, Types $types): Type
            {
                if ($type !== 'type1') {
                    throw new DomainException;
                }

                return new self;
            }

            public function normalize($data)
            {
            }

            public function denormalize($data)
            {
            }

            public function __toString(): string
            {
                return 'type1';
            }
        };
        $type2 = new class implements Type {
            public static function fromString(string $type, Types $types): Type
            {
                if ($type !== 'type2') {
                    throw new DomainException;
                }

                return new self;
            }

            public function normalize($data)
            {
            }

            public function denormalize($data)
            {
            }

            public function __toString(): string
            {
                return 'type2';
            }
        };
        $class1 = \get_class($type1);
        $class2 = \get_class($type2);
        $types = new Types($class1, $class2);

        $this->assertInstanceOf($class1, $types->build('type1'));
        $this->assertInstanceOf($class2, $types->build('type2'));
    }

    /**
     * @expectedException Innmind\Rest\Client\Exception\UnknownType
     */
    public function testThrowWhenBuildingUnknownType()
    {
        (new Types)->build('type1');
    }

    public function testDefaults()
    {
        $defaults = Types::defaults();

        $this->assertInstanceOf(SetInterface::class, $defaults);
        $this->assertSame('string', (string) $defaults->type());
        $this->assertCount(7, $defaults);
        $this->assertSame(
            [
                BoolType::class,
                DateType::class,
                FloatType::class,
                IntType::class,
                MapType::class,
                SetType::class,
                StringType::class,
            ],
            $defaults->toPrimitive()
        );

        $this->assertInstanceOf(BoolType::class, (new Types)->build('bool'));
    }
}
