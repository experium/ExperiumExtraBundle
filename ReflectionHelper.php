<?php

namespace Experium\ExtraBundle;

/**
 * @author Alexey Shockov <shokov@experium.ru>
 */
class ReflectionHelper
{
    public static function getClassName($object)
    {
        $class = new \ReflectionObject($object);

        return $class->getShortName();
    }

    public static function updateProperty($object, $property, $value)
    {
        $property = static::getProperty(new \ReflectionObject($object), $property);

        if (!$property) {
            throw new \InvalidArgumentException('Property not found.');
        }

        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    private static function getProperty(\ReflectionClass $class, $property)
    {
        if ($class->hasProperty($property)) {
            return $class->getProperty($property);
        } else {
            if (!$class->getParentClass()) {
                return null;
            }

            return static::getProperty($class->getParentClass(), $property);
        }
    }
}
