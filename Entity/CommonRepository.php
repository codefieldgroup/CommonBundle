<?php
namespace cf\CommonBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;


class CommonRepository extends EntityRepository
{

    /**
     * @param array $criteria
     * @param $count
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     *
     * @return array
     */
    public function searchOr( array $criteria, &$count, array $orderBy = null, $limit = null, $offset = null )
    {
        $em = $this->getEntityManager();
        //		$expr = $em->getExpressionBuilder();

        //get Count
        $qb_count = $em->createQueryBuilder();
        $qb_count->select( 'COUNT(entity.id)' )->from( $this->getEntityName(), 'entity' );
        if (is_array( $criteria ) && count( $criteria ) > 0) {
            foreach ($criteria as $key => $value) {
                $qb_count = $qb_count->orWhere( 'entity.'.$key.' LIKE '.':value'.$key );
                $qb_count->setParameter( 'value'.$key, '%'.$value.'%' );
            }
        }
        $count = $qb_count->getQuery()->getSingleScalarResult();

        if ($count <= 0) {
            return [ ];
        }

        //Do Query
        $qb = $em->createQueryBuilder();
        $qb->select( 'entity' )->from( $this->getEntityName(), 'entity' );

        if (is_array( $criteria ) && count( $criteria ) > 0) {
            foreach ($criteria as $key => $value) {
                $qb = $qb->orWhere( 'entity.'.$key.' LIKE '.':value'.$key );
                $qb->setParameter( 'value'.$key, '%'.$value.'%' );
            }
        }
        if ($orderBy !== null && $orderBy) {
            $qb = $qb->orderBy( $orderBy );
        }
        if ($limit !== null && is_numeric( $limit )) {
            $qb->setMaxResults( $limit );
        }
        if ($offset !== null && is_numeric( $offset )) {
            $qb->setFirstResult( $offset );
        }
        $entities = $qb->getQuery()->getResult();

        return $entities;
    }

    /**
     * @param array $criteria
     * @param $count
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     *
     * @return array
     */
    public function searchAnd( array $criteria, &$count, array $orderBy = null, $limit = null, $offset = null )
    {
        $em = $this->getEntityManager();
        //		$expr = $em->getExpressionBuilder();

        //get Count
        $qb_count = $em->createQueryBuilder();
        $qb_count->select( 'COUNT(entity.id)' )->from( $this->getEntityName(), 'entity' );
        if (is_array( $criteria ) && count( $criteria ) > 0) {
            foreach ($criteria as $key => $value) {
                $qb_count = $qb_count->andWhere( 'entity.'.$key.' LIKE '.':value'.$key );
                $qb_count->setParameter( 'value'.$key, '%'.$value.'%' );
            }
        }
        $count = $qb_count->getQuery()->getSingleScalarResult();

        //Do Query
        $qb = $em->createQueryBuilder();
        $qb->select( 'entity' )->from( $this->getEntityName(), 'entity' );

        if (is_array( $criteria ) && count( $criteria ) > 0) {
            foreach ($criteria as $key => $value) {
                $qb = $qb->andWhere( 'entity.'.$key.' LIKE '.':value'.$key );
                $qb->setParameter( 'value'.$key, '%'.$value.'%' );
            }
        }
        if ($orderBy !== null) {
            $qb = $qb->orderBy( $orderBy );
        }
        if ($limit !== null && is_numeric( $limit )) {
            $qb->setMaxResults( $limit );
        }
        if ($offset !== null && is_numeric( $offset )) {
            $qb->setFirstResult( $offset );
        }
        $entities = $qb->getQuery()->getResult();

        return $entities;
    }
}