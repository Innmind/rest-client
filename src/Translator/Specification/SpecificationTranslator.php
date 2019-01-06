<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Translator\Specification;

use Innmind\Rest\Client\{
    Translator\SpecificationTranslator as SpecificationTranslatorInterface,
    Exception\OnlyEqualityCanBeTranslated,
    Exception\OnlyAndCompositionCanBeTranslated,
    Exception\SpecificationCantBeTranslated,
};
use Innmind\Specification\{
    Specification,
    Comparator,
    Composite,
    Operator,
    Sign,
};

/**
 * {@inheritdoc}
 */
final class SpecificationTranslator implements SpecificationTranslatorInterface
{
    public function translate(Specification $specification): string
    {
        switch (true) {
            case $specification instanceof Comparator:
                if (!$specification->sign()->equals(Sign::equality())) {
                    throw new OnlyEqualityCanBeTranslated;
                }

                return sprintf(
                    '%s=%s',
                    $specification->property(),
                    $specification->value()
                );

            case $specification instanceof Composite:
                if ($specification->operator()->equals(Operator::or())) {
                    throw new OnlyAndCompositionCanBeTranslated;
                }

                return sprintf(
                    '%s&%s',
                    $this->translate($specification->left()),
                    $this->translate($specification->right())
                );

            default:
                throw new SpecificationCantBeTranslated;
        }
    }
}
