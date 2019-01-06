<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Translator;

use Innmind\Specification\Specification;

/**
 * Build an http query out of a specification
 */
interface SpecificationTranslator
{
    public function translate(Specification $specification): string;
}
