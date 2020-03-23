<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\{
    Definition\Identity,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class IdentityTest extends TestCase
{
    public function testThrowWhenEmptyIdentity()
    {
        $this->expectException(DomainException::class);

        new Identity('');
    }

    public function testInterface()
    {
        $this->assertSame(
            'foo',
            (string) new Identity('foo')
        );
    }
}
