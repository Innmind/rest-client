<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Definition;

use Innmind\Rest\Client\Definition\Identity;

class IdentityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Innmind\Rest\Client\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyIdentity()
    {
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
