<?php

namespace Innmind\Rest\Client;

use Innmind\Rest\Client\Definition\Resource as Definition;
use Innmind\Rest\Client\Serializer\Normalizer\ResourceNormalizer;
use Symfony\Component\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class Validator
{
    protected $validator;
    protected $normalizer;

    public function __construct(
        ValidatorInterface $validator,
        ResourceNormalizer $normalizer
    ) {
        $this->validator = $validator;
        $this->normalizer = $normalizer;
    }

    /**
     * Validate the resource against the given definition
     *
     * @param HttpResource $resource
     * @param Definition $definition
     * @param string $action
     *
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public function validate(
        HttpResource $resource,
        Definition $definition,
        $action
    ) {
        if (!in_array($action, [Action::CREATE, Action::UPDATE], true)) {
            throw new \InvalidArgumentException(sprintf(
                'The action must either be "%s" or "%s"',
                Action::CREATE,
                Action::UPDATE
            ));
        }

        $data = $this->normalizer->normalize($resource, null, [
            'definition' => $definition,
            'action' => $action,
        ]);
        $constraint = $this->buildConstraint($definition, $action);

        return $this->validator->validate($data['resource'], [$constraint]);
    }

    /**
     * Build the constraint tree for the given definition
     *
     * @param Definition $definition
     * @param string $action
     *
     * @return Assert\Collection
     */
    protected function buildConstraint(Definition $definition, $action)
    {
        $fields = [];

        foreach ($definition->getProperties() as $prop) {
            if (!$prop->hasAccess($action)) {
                continue;
            }

            $fields[(string) $prop] = [];

            if (!$prop->isOptional()) {
                $fields[(string) $prop][] = new Assert\NotNull;
            }

            switch ($prop->getType()) {
                case 'date':
                    $fields[(string) $prop] = new Assert\Callback(function(
                        $data,
                        ExecutionContextInterface $context
                    )
                    use (
                        $prop
                    ) {
                        if ($data instanceof \DateTime) {
                            return;
                        }
                        if (!is_string($data)) {
                            $context
                                ->buildViolation('This field must be a date')
                                ->atPath((string) $prop)
                                ->addViolation();
                            return;
                        }

                        try {
                            new \DateTime($data);
                        } catch (\Exception $e) {
                            $context
                                ->buildViolation('This field must be a date')
                                ->atPath((string) $prop)
                                ->addViolation();
                        }
                    });
                    break;
                case 'array':
                    $fields[(string) $prop] = new Assert\Callback(function(
                        $data,
                        ExecutionContextInterface $context
                    )
                    use (
                        $prop
                    ) {
                        if (
                            !is_array($data) &&
                            !$data instanceof \Traversable
                        ) {
                            $context
                                ->buildViolation(
                                    'It must be an array or an object implementing ' .
                                    '\ArrayAccess or \Traversable'
                                )
                                ->atPath((string) $prop)
                                ->addViolation();
                            return;
                        }
                    });
                    break;
                case 'string':
                case 'int':
                case 'float':
                case 'bool':
                    $fields[(string) $prop][] = new Assert\Type([
                        'type' => $prop->getType()
                    ]);
                    break;
            }

            if ($prop->containsResource()) {
                $fields[(string) $prop] = new Assert\All([
                    'constraints' => [$this->buildConstraint(
                        $prop->getResource(),
                        $action
                    )],
                ]);
            }

            foreach ($prop->getVariants() as $variant) {
                $fields[$variant] = $fields[(string) $prop];
            }
        }

        return new Assert\Collection([
            'fields' => $fields,
            'allowMissingFields' => true,
        ]);
    }
}
