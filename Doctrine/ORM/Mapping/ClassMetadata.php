<?php
namespace Experium\ExtraBundle\Doctrine\ORM\Mapping;

use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\Mapping\ClassMetadata as BaseClassMetadata;

class ClassMetadata extends BaseClassMetadata
{
    public function __construct($entityName)
    {
        parent::__construct($entityName);

        // For 2.1.x.
        $this->setTableName(Inflector::tableize($this->getTableName()));
    }

    /**
     * {@inheritdoc}
     */
    public function initializeReflection($reflService)
    {
        parent::initializeReflection($reflService);

        // For 2.2.x.
        $this->setPrimaryTable([
            'name' => Inflector::tableize($this->getTableName())
        ]);
    }

    private function normalizeMapping(array $mapping)
    {
        if (isset($mapping['joinColumns'])) {
            foreach ($mapping['joinColumns'] as &$joinColumn) {
                if (is_null($joinColumn['name'])) {
                    $joinColumn['name'] = Inflector::tableize(
                        $mapping['fieldName'].'_'.$joinColumn['referencedColumnName']
                    );
                }
            }
        } else {
            if (!isset($mapping['columnName'])) {
                $mapping['columnName'] = Inflector::tableize($mapping['fieldName']);
            }
        }

        return $mapping;
    }

    public function mapField(array $mapping)
    {
        parent::mapField($this->normalizeMapping($mapping));
    }

    public function mapManyToMany(array $mapping)
    {
        parent::mapManyToMany($this->normalizeMapping($mapping));
    }

    public function mapManyToOne(array $mapping)
    {
        parent::mapManyToOne($this->normalizeMapping($mapping));
    }

    public function mapOneToMany(array $mapping)
    {
        parent::mapOneToMany($this->normalizeMapping($mapping));
    }

    public function mapOneToOne(array $mapping)
    {
        parent::mapOneToOne($this->normalizeMapping($mapping));
    }
}
