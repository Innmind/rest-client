<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Exception\{
    InvalidArgumentException,
    UnknownTypeException
};

final class Types
{
    private $types = [];

    public function register(string $class): self
    {
        $refl = new \ReflectionClass($class);

        if (!$refl->implementsInterface(TypeInterface::class)) {
            throw new InvalidArgumentException;
        }

        $this->types[] = $class;

        return $this;
    }

    public function build(string $type): TypeInterface
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
}
