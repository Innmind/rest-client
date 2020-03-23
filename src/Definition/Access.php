<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Definition;

use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;

final class Access
{
    const READ = 'READ';
    const CREATE = 'CREATE';
    const UPDATE = 'UPDATE';

    /** @var Set<string> */
    private Set $mask;

    public function __construct(string ...$mask)
    {
        $this->mask = Set::strings(...$mask);
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
     * @return Set<string>
     */
    public function mask(): Set
    {
        return $this->mask;
    }

    public function matches(self $mask): bool
    {
        foreach (unwrap($mask->mask()) as $access) {
            if (!$this->mask->contains($access)) {
                return false;
            }
        }

        return true;
    }
}
