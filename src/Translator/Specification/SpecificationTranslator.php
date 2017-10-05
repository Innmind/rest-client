<?php
declare(strict_types = 1);

namespace Innmind\Rest\Client\Translator\Specification;

use Innmind\Rest\Client\{
    Translator\SpecificationTranslator as SpecificationTranslatorInterface,
    Exception\OnlyEqualityCanBeTranslatedException,
    Exception\OnlyAndCompositionCanBeTranslatedException,
    Exception\SpecificationCantBeTranslatedException
};
use Innmind\Specification\{
    SpecificationInterface,
    ComparatorInterface,
    CompositeInterface,
    Operator
};

/**
 * {@inheritdoc}
 */
final class SpecificationTranslator implements SpecificationTranslatorInterface
{
    public function translate(SpecificationInterface $specification): string
    {
        switch (true) {
            case $specification instanceof ComparatorInterface:
                if ($specification->sign() !== '==') {
                    throw new OnlyEqualityCanBeTranslatedException;
                }

                return sprintf(
                    '%s=%s',
                    $specification->property(),
                    $specification->value()
                );

            case $specification instanceof CompositeInterface:
                if ((string) $specification->operator() === Operator::OR) {
                    throw new OnlyAndCompositionCanBeTranslatedException;
                }

                return sprintf(
                    '%s&%s',
                    $this->translate($specification->left()),
                    $this->translate($specification->right())
                );

            default:
                throw new SpecificationCantBeTranslatedException;
        }
    }
}
