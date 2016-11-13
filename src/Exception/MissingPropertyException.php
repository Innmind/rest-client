<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Exception;

final class MissingPropertyException extends RuntimeException
{
    public function __construct(string $property)
    {
        parent::__construct(
            sprintf(
                'Missing property "%s"',
                $property
            )
        );
    }
}
