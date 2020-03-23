<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Identity;

use Innmind\Rest\Client\{
    Identity\Identity,
    Identity as IdentityInterface,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class IdentityTest extends TestCase
{
    public function testInterface()
    {
        $identity = new Identity('foo');

        $this->assertInstanceOf(IdentityInterface::class, $identity);
        $this->assertSame('foo', (string) $identity);
    }

    public function testThrowWhenEmptyValue()
    {
        $this->expectException(DomainException::class);

        new Identity('');
    }
}
