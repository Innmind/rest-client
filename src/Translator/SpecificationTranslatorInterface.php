<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Translator;

use Innmind\Specification\SpecificationInterface;

/**
 * Build an http query out of a specification
 */
interface SpecificationTranslatorInterface
{
    public function translate(SpecificationInterface $specification): string;
}
