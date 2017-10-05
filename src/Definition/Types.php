<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\{
    Definition\Type\BoolType,
    Definition\Type\DateType,
    Definition\Type\FloatType,
    Definition\Type\IntType,
    Definition\Type\MapType,
    Definition\Type\SetType,
    Definition\Type\StringType,
    Exception\InvalidArgumentException,
    Exception\UnknownTypeException
};
use Innmind\Immutable\{
    Set,
    SetInterface
};

final class Types
{
    private static $defaults;
    private $types = [];

    public function register(string $class): self
    {
        $refl = new \ReflectionClass($class);

        if (!$refl->implementsInterface(Type::class)) {
            throw new InvalidArgumentException;
        }

        $this->types[] = $class;

        return $this;
    }

    public function build(string $type): Type
    {
        foreach ($this->types as $builder) {
            try {
                return call_user_func(
                    [$builder, 'fromString'],
                    $type,
                    $this
                );
            } catch (InvalidArgumentException $e) {
                //pass
            }
        }

        throw new UnknownTypeException;
    }

    /**
     * @return SetInterface<string>
     */
    public static function defaults(): SetInterface
    {
        if (self::$defaults === null) {
            self::$defaults = (new Set('string'))
                ->add(BoolType::class)
                ->add(DateType::class)
                ->add(FloatType::class)
                ->add(IntType::class)
                ->add(MapType::class)
                ->add(SetType::class)
                ->add(StringType::class);
        }

        return self::$defaults;
    }
}
