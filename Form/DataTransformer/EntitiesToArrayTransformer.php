<?php

namespace Experium\ExtraBundle\Form\DataTransformer;

use Experium\ExtraBundle\Form\ChoiceList\EntityChoiceList;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

class EntitiesToArrayTransformer implements DataTransformerInterface
{
    private $choiceList;

    public function __construct(EntityChoiceList $choiceList)
    {
        $this->choiceList = $choiceList;
    }

    /**
     * Transforms entities into choice keys
     *
     * @param Collection|object $collection A collection of entities, a single entity or
     *                                      NULL
     * @return mixed An array of choice keys, a single key or NULL
     */
    public function transform($collection)
    {
        if (null === $collection) {
            return array();
        }

        if (!($collection instanceof Collection)) {
            throw new UnexpectedTypeException($collection, 'Doctrine\Common\Collections\Collection');
        }

        $array = array();

        foreach ($collection as $entity) {
            $value = $entity->getId();
            $array[] = is_numeric($value) ? (int) $value : $value;
        }

        return $array;
    }

    /**
     * Transforms choice keys into entities
     *
     * @param  mixed $keys   An array of keys, a single key or NULL
     * @return Collection|object  A collection of entities, a single entity
     *                            or NULL
     */
    public function reverseTransform($keys)
    {
        $collection = new ArrayCollection();

        if ('' === $keys || null === $keys) {
            return $collection;
        }

        if (!is_array($keys)) {
            throw new UnexpectedTypeException($keys, 'array');
        }

        $notFound = array();

        // optimize this into a SELECT WHERE IN query
        foreach ($keys as $key) {
            if ($entity = $this->choiceList->getEntity($key)) {
                $collection->add($entity);
            } else {
                $notFound[] = $key;
            }
        }

        if (count($notFound) > 0) {
            throw new TransformationFailedException(sprintf('The entities with keys "%s" could not be found', implode('", "', $notFound)));
        }

        return $collection;
    }
}
