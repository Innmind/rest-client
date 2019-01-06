<?php
declare(strict_types = 1);

namespace Tests\Innmind\Rest\Client\Serializer\Denormalizer;

use Innmind\Rest\Client\Serializer\Denormalizer\DenormalizeCapabilitiesNames;
use Innmind\Immutable\SetInterface;
use PHPUnit\Framework\TestCase;

class DenormalizeCapabilitiesNamesTest extends TestCase
{
    public function testDenormalize()
    {
        $names = (new DenormalizeCapabilitiesNames)(['foo', 'bar']);

        $this->assertInstanceOf(SetInterface::class, $names);
        $this->assertSame('string', (string) $names->type());
        $this->assertSame(['foo', 'bar'], $names->toPrimitive());
    }
}
