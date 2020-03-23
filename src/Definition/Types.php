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
    Exception\DomainException,
    Exception\UnknownType,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;

final class Types
{
    private static ?Set $defaults = null;
    /** @var list<class-string<Type>> */
    private array $types = [];

    /**
     * @param list<class-string<Type>> $types
     */
    public function __construct(string ...$types)
    {
        if (\count($types) === 0) {
            $types = unwrap(self::defaults());
        }

        /** @var class-string<Type> $type */
        foreach ($types as $type) {
            $refl = new \ReflectionClass($type);

            if (!$refl->implementsInterface(Type::class)) {
                throw new DomainException;
            }
        }

        /** @var list<class-string<Type>> */
        $this->types = $types;
    }

    public function __invoke(string $type): Type
    {
        foreach ($this->types as $builder) {
            try {
                /** @var Type */
                return call_user_func(
                    [$builder, 'of'],
                    $type,
                    $this
                );
            } catch (DomainException $e) {
                //pass
            }
        }

        throw new UnknownType;
    }

    /**
     * @return Set<string>
     */
    public static function defaults(): Set
    {
        /** @var Set<string> */
        return self::$defaults ??= Set::strings(
            BoolType::class,
            DateType::class,
            FloatType::class,
            IntType::class,
            MapType::class,
            SetType::class,
            StringType::class,
        );
    }
}
