<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Immutable\{
    SetInterface,
    Set,
};

final class Access
{
    const READ = 'READ';
    const CREATE = 'CREATE';
    const UPDATE = 'UPDATE';

    private $mask;

    public function __construct(string ...$mask)
    {
        $this->mask = Set::of('string', ...$mask);
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
