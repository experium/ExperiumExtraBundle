<?php

namespace Experium\ExtraBundle\Collection;

class EntityCollection implements \IteratorAggregate, \Countable
{
    private $queryBuilder;

    private $entities;

    private $entityCount;

    public function __construct($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function fetchSlice($offset, $limit)
    {
        return $this->queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
    }

    public function fetchAll()
    {
        return $this->queryBuilder
            ->getQuery()
            ->execute();
    }

    public function count()
    {
        if ($this->entities) {
            return count($this->entities);
        } else {
            if (is_null($this->entityCount)) {
                $alias = $this->queryBuilder->getRootAlias();

                $queryBuilder = clone $this->queryBuilder;

                $this->entityCount = (int) $queryBuilder
                    ->select('COUNT('.$alias.')')
                    ->resetDQLPart('orderBy')
                    ->setMaxResults(null)
                    ->setFirstResult(null)
                    ->getQuery()
                    ->getSingleScalarResult();
            }

            return $this->entityCount;
        }
    }

    public function getIterator()
    {
        return $this->fetchAll();
    }
}
