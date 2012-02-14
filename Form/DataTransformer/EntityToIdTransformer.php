<?php

namespace Experium\ExtraBundle\Form\DataTransformer;

use Experium\ExtraBundle\Form\ChoiceList\EntityChoiceList;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\Common\Collections\Collection;

class EntityToIdTransformer implements DataTransformerInterface
{
    private $choiceList;

    public function __construct(EntityChoiceList $choiceList)
    {
        $this->choiceList = $choiceList;
    }

    /**
     * Transforms entities into choice keys
     *
     * @param Collection|object $entity A collection of entities, a single entity or
     *                                  NULL
     * @return mixed An array of choice keys, a single key or NULL
     */
    public function transform($entity)
    {
        if (null === $entity || '' === $entity) {
            return '';
        }

        if (!is_object($entity)) {
            throw new UnexpectedTypeException($entity, 'object');
        }

        if ($entity instanceof Collection) {
            throw new \InvalidArgumentException('Expected an object, but got a collection. Did you forget to pass "multiple=true" to an entity field?');
        }

        return $entity->getId();
    }

    /**
     * Transforms choice keys into entities
     *
     * @param  mixed $key   An array of keys, a single key or NULL
     * @return Collection|object  A collection of entities, a single entity
     *                            or NULL
     */
    public function reverseTransform($key)
    {
        if ('' === $key || null === $key) {
            return null;
        }

        if (!($entity = $this->choiceList->getEntity($key))) {
            throw new TransformationFailedException(sprintf('The entity with key "%s" could not be found', $key));
        }

        return $entity;
    }
}
