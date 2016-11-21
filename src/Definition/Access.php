<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Exception\InvalidArgumentException;
use Innmind\Immutable\SetInterface;

final class Access
{
    const READ = 'READ';
    const CREATE = 'CREATE';
    const UPDATE = 'UPDATE';

    private $mask;

    public function __construct(SetInterface $mask)
    {
        if ((string) $mask->type() !== 'string') {
            throw new InvalidArgumentException;
        }

        $this->mask = $mask;
    }

    public function isReadable(): bool
    {
        return $this->mask->contains(self::READ);
    }

    public function isCreatable(): bool
    {
        return $this->mask->contains(self::CREATE);
    }

    public function isUpdatable(): bool
    {
        return $this->mask->contains(self::UPDATE);
    }

    /**
     * @return SetInterface<string>
     */
    public function mask(): SetInterface
    {
        return $this->mask;
    }

    public function matches(self $mask): bool
    {
        foreach ($mask->mask() as $access) {
            if (!$this->mask->contains($access)) {
                return false;
            }
        }

        return true;
    }
}
