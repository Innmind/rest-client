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
    Exception\UnknownType,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testThrowWhenRegisteringInvalidType()
    {
        $this->expectException(DomainException::class);

        new Types('stdClass');
    }

    public function testBuild()
    {
        $type1 = new class implements Type {
            public static function fromString(string $type, Types $build): Type
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

            public function toString(): string
            {
                return 'type1';
            }
        };
        $type2 = new class implements Type {
            public static function fromString(string $type, Types $build): Type
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

            public function toString(): string
            {
                return 'type2';
            }
        };
        $class1 = \get_class($type1);
        $class2 = \get_class($type2);
        $build = new Types($class1, $class2);

        $this->assertInstanceOf($class1, $build('type1'));
        $this->assertInstanceOf($class2, $build('type2'));
    }

    public function testThrowWhenBuildingUnknownType()
    {
        $this->expectException(UnknownType::class);

        (new Types)('type1');
    }

    public function testDefaults()
    {
        $defaults = Types::defaults();

        $this->assertInstanceOf(Set::class, $defaults);
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
            unwrap($defaults)
        );

        $this->assertInstanceOf(BoolType::class, (new Types)('bool'));
    }
}
