<?php

namespace Innmind\Rest\Client\Tests;

use Innmind\Rest\Client\Validator;
use Innmind\Rest\Client\HttpResource;
use Innmind\Rest\Client\Definition\ResourceDefinition as Definition;
use Innmind\Rest\Client\Definition\Property;
use Innmind\Rest\Client\Serializer\Normalizer\ResourceNormalizer;
use Symfony\Component\Validator\Validation;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    protected $v;

    public function setUp()
    {
        $this->v = new Validator(
            Validation::createValidator(),
            new ResourceNormalizer
        );
    }

    public function testValidate()
    {
        $sub = new Definition(
            '',
            '',
            ['a' => new Property('a', 'string', ['CREATE'], [])]
        );
        $d = new Definition(
            '',
            '',
            [
                'a' => new Property('a', 'string', ['CREATE'], ['z']),
                'b' => new Property('b', 'int', ['CREATE'], []),
                'c' => new Property('c', 'float', ['CREATE'], []),
                'd' => new Property('d', 'bool', ['CREATE'], [], true),
                'e' => (new Property('e', 'array', ['CREATE'], [], false, 'resource'))
                    ->linkTo($sub),
                'f' => new Property('f', 'date', ['CREATE'], []),
            ]
        );
        $date = new \DateTime;
        $r = new HttpResource;
        $r
            ->set('a', 'foo')
            ->set('b', 42)
            ->set('c', 42.0)
            ->set('d', false)
            ->set('e', [['a' => 'foo']])
            ->set('f', $date->format(\DateTime::ISO8601));

        $violations = $this->v->validate($r, $d, 'CREATE');
        $this->assertSame(0, $violations->count());

        $r->remove('d');
        $violations = $this->v->validate($r, $d, 'CREATE');
        $this->assertSame(0, $violations->count());

        $r->set('f', $date);
        $violations = $this->v->validate($r, $d, 'CREATE');
        $this->assertSame(0, $violations->count());

        $r
            ->set('z', 'foo')
            ->remove('a');
        $violations = $this->v->validate($r, $d, 'CREATE');
        $this->assertSame(0, $violations->count());

        $r->set('b', '42');
        $violations = $this->v->validate($r, $d, 'CREATE');
        $this->assertSame(1, $violations->count());
        $this->assertSame(
            'Array[b]:' . "\n" . '    This value should be of type int.' . "\n",
            (string) $violations
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The action must either be "CREATE" or "UPDATE"
     */
    public function testThrowIfInvalidAction()
    {
        $this->v->validate(new HttpResource, new Definition('', '', []), 'foo');
    }
}
