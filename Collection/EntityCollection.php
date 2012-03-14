<?php

namespace Experium\ExtraBundle\Collection;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\PersistentCollection;

use Doctrine\Common\Collections\Collection;

use Experium\ExtraBundle\CallbackIteratorDecorator;

/**
 * @author Vyacheslav Salakhutdinov <salakhutdinov@experium.ru>
 * @author Alexey Shockov <shokov@experium.ru>
 */
class EntityCollection implements \IteratorAggregate, \Countable
{
    protected $queryBuilder;

    private $entities;

    private $cacher;

    /**
     * Создаём коллекцию для ассоциации Доктрины. Внутри появляется QueryBuilder, через который можно гибко
     * фильтровать. Частично аналог того, что будет в 2.3.
     *
     * @see \Doctrine\ORM\PersistentCollection::getOwner()
     * @see \Doctrine\ORM\PersistentCollection::getMapping()
     */
    private static function fromDoctrinePersistentCollection(PersistentCollection $collection)
    {
        // Пошли в reflection... Больше никак не достать. Костыль, конечно.
        $collectionClass       = new \ReflectionClass($collection);
        $entityManagerProperty = $collectionClass->getProperty('em');
        $entityManagerProperty->setAccessible(true);

        $entityManager = $entityManagerProperty->getValue($collection);
        $owner         = $collection->getOwner(); // Собтвенно, владелец, для которого грузим коллекцию.
        $mapping       = $collection->getMapping();

        $queryBuilder = $entityManager
            ->getRepository($mapping['targetEntity'])
            ->createQueryBuilder('target')
            // Для один-много (стороны, где много), будет всегда примерно так. А вот для много-много, если связь только с
            // одной стороны...
            ->where('target.'.$mapping['mappedBy'].' = :owner')
            ->setParameter('owner', $owner);

        return static::fromQueryBuilder($queryBuilder);
    }

    public static function fromDoctrineCollection(Collection $collection)
    {
        if (
            ($collection instanceof PersistentCollection)
            &&
            !$collection->isInitialized()
        ) {
            return self::fromDoctrinePersistentCollection($collection);
        } else {
            // toArray() есть только в ArrayCollection, но не в общем интерфейсе Collection.
            return static::fromArray(iterator_to_array($collection));
        }
    }

    /**
     * @internal
     */
    public static function fromQueryBuilder(QueryBuilder $queryBuilder)
    {
        $collection = new static();

        $collection->queryBuilder = $queryBuilder;

        return $collection;
    }

    /**
     * @internal
     */
    public static function fromArray(array $entities)
    {
        $collection = new static();

        $this->entities = $entities;

        return $collection;
    }

    // TODO Сделать конструктор пустым, приватным.
    public function __construct($queryBuilder = null)
    {
        $this->queryBuilder = $queryBuilder;

        $this->enableCaching();
    }

    /**
     * Save objects references inside (attach to UnitOfWork in Doctrine). Default behaviour.
     */
    public function enableCaching()
    {
        $this->cacher = function() {};
    }

    public function disableCaching()
    {
        if ($this->queryBuilder) {
            $entityManager = $this->queryBuilder->getEntityManager();

            $this->cacher = function($entity) use($entityManager) { $entityManager->detach($entity); };
        }
    }

    /**
     * @deprecated For getSlice().
     */
    public function fetchSlice($offset, $limit)
    {
        return $this->getSlice($offset, $limit);
    }

    /**
     * Entities are detached from EM.
     *
     * @param $offset
     * @param $limit
     *
     * @return EntityCollection
     */
    public function getSlice($offset, $limit)
    {
        if ($this->queryBuilder) {
            $queryBuilder = clone $this->queryBuilder;

            $entities = $queryBuilder
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();

            array_walk($entities, $this->cacher);
        } else {
            $entities = array_slice(iterator_to_array($this), $offset, $limit);
        }

        return static::fromArray($entities);
    }

    public function count()
    {
        return $this->modifyQueryBuilderByReducer(
            function($queryBuilder) {
                $alias = $queryBuilder->getRootAliases();
                $alias = $alias[0];

                return (int) $queryBuilder
                    ->select('COUNT('.$alias.')')
                    ->resetDQLPart('orderBy')
                    ->setMaxResults(null)
                    ->setFirstResult(null)
                    ->getQuery()
                    ->getSingleScalarResult();
            },
            function($entities) {
                return count($entities);
            }
        );
    }

    /**
     * Entities are detached from EM.
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        if ($this->queryBuilder) {
            $iterator = $this->queryBuilder
                ->getQuery()
                ->iterate();

            $cacher = $this->cacher;

            return new CallbackIteratorDecorator(
                $iterator,
                function($index, $row) use ($cacher) {
                    $entity = $row[0];

                    call_user_func($cacher, $entity);

                    return array($index, $entity);
                }
            );
        } else {
            return new ArrayIterator($this->entities);
        }
    }

    protected function modifyByFilter($filter)
    {
        if (!is_callable($filter)) {
            throw new \InvalidArgumentException();
        }

        return static::fromArray(
            array_filter(iterator_to_array($this), $filter)
        );
    }

    protected function modifyQueryBuilderByFilter($qbFilter, $filter)
    {
        if ($this->queryBuilder) {
            $queryBuilder = clone $this->queryBuilder;

            $alias = $queryBuilder->getRootAliases();
            $alias = $alias[0];

            call_user_func($qbFilter, $queryBuilder, $alias);

            return new static::fromQueryBuilder($queryBuilder);
        } else {
            return $this->modifyByFilter($filter);
        }
    }

    protected function modifyByComparator($comparator)
    {
        if (!is_callable($comparator)) {
            throw new \InvalidArgumentException();
        }

        $entities = iterator_to_array($this);

        usort($entities, $comparator);

        return static::fromArray($entities);
    }

    protected function modifyQueryBuilderByComparator($qbComparator, $comparator)
    {
        if ($this->queryBuilder) {
            $queryBuilder = clone $this->queryBuilder;

            $alias = $queryBuilder->getRootAliases();
            $alias = $alias[0];

            call_user_func($qbComparator, $queryBuilder, $alias);

            return new static::fromQueryBuilder($queryBuilder);
        } else {
            return $this->modifyByComparator($comparator);
        }
    }

    protected function modifyByReducer($reducer)
    {
        if (!is_callable($reducer)) {
            throw new \InvalidArgumentException();
        }

        return array_reduce(iterator_to_array($this), $reducer);
    }

    protected function modifyQueryBuilderByReducer($qbReducer, $reducer)
    {
        if ($this->queryBuilder) {
            $queryBuilder = clone $this->queryBuilder;

            $alias = $queryBuilder->getRootAliases();
            $alias = $alias[0];

            return call_user_func($qbReducer, $queryBuilder, $alias);
        } else {
            return $this->modifyByReducer($reducer);
        }
    }
}
