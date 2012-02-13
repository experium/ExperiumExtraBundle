<?php

namespace Experium\ExtraBundle\Doctrine\ORM\Mapping;

class ClassMetadataFactory extends \Doctrine\ORM\Mapping\ClassMetadataFactory
{
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }
}
