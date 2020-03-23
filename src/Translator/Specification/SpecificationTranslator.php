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

final class SpecificationTranslator implements SpecificationTranslatorInterface
{
    public function __invoke(Specification $specification): string
    {
        switch (true) {
            case $specification instanceof Comparator:
                if (!$specification->sign()->equals(Sign::equality())) {
                    throw new OnlyEqualityCanBeTranslated;
                }

                /** @psalm-suppress MixedArgument */
                return \sprintf(
                    '%s=%s',
                    $specification->property(),
                    $specification->value(),
                );

            case $specification instanceof Composite:
                if ($specification->operator()->equals(Operator::or())) {
                    throw new OnlyAndCompositionCanBeTranslated;
                }

                return \sprintf(
                    '%s&%s',
                    $this($specification->left()),
                    $this($specification->right()),
                );

            default:
                throw new SpecificationCantBeTranslated;
        }
    }
}
