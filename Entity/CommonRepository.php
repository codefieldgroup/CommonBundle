<?php
namespace Cf\CommonBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use \Doctrine\Common\Util\Inflector as Inflector;

class CommonRepository extends EntityRepository
{

    /**
     * Prepare attributes for entity
     * replace foreign keys with entity instances
     *
     * @param array $attributes entity attributes
     *
     * @return array modified attributes values
     */
    public function prepareAttributes( array $attributes )
    {
        foreach ($attributes as $fieldName => &$fieldValue) {

            if ( ! $this->getClassMetadata()->hasAssociation( Inflector::camelize( $fieldName ) )) {
                continue;
            }

            $association = $this->getClassMetadata()->getAssociationMapping( Inflector::camelize( $fieldName ) );

            if ($fieldValue === null) {
                continue;
            }

            if (is_array( $fieldValue ) && ! array_key_exists( 'id', $fieldValue )) {
                continue;
            }

            if (is_string( $fieldValue )) {
                $tmp_fieldValue = json_decode( $fieldValue, JSON_OBJECT_AS_ARRAY );

                if (array_key_exists( 'id', $tmp_fieldValue )) {
                    $fieldValue = $tmp_fieldValue;
                } else {
                    continue;
                }
            }

            $fieldValue = $this->getEntityManager()->getReference( $association['targetEntity'], $fieldValue['id'] );

            unset( $fieldValue );
        }

        return $attributes;
    }

    /**
     * Search with OR | AND conditions.
     *
     * You can search intro relations, for ex:
     * entity cfUser has:
     * username
     * password
     * cfPerson
     *
     * entity cfPerson has:
     * firstname
     * lastname
     * personalId
     *
     * and the query by criteria:
     *
     * | -> OR
     * & -> AND
     *
     * $criteria = [ '|username' => 'john', '&cfPerson.firstname' => 'john', '|cfPerson.personalId' => 'john' ];
     *
     * @param array $criteria
     * @param $count
     * @param array $orderBy Ex. [ 'dateTurn', 'DESC' ] or [ 'dateTurn', 'ASC' ]
     * @param null $limit
     * @param null $offset
     *
     * @return array
     */
    public function search( array $criteria, &$count, array $orderBy = null, $limit = null, $offset = null )
    {
        $em = $this->getEntityManager();
        //		$expr = $em->getExpressionBuilder();
        //get Count
        $qb_count = $em->createQueryBuilder();
        $qb_count->select( 'COUNT(entity.id)' )->from( $this->getEntityName(), 'entity' );
        if (is_array( $criteria ) && count( $criteria ) > 0) {
            $join_name       = [ ];
            $operation_and   = null;
            $operation_or    = null;
            $iterator_search = 0;
            foreach ($criteria as $key => $value) {
                //get operator <, > or =
                $get_operator_minor       = explode( '<', $key );
                $get_operator_minor_equal = explode( '<=', $key );
                $get_operator_major       = explode( '>', $key );
                $get_operator_major_equal = explode( '>=', $key );
                $get_operator_equal       = explode( '=', $key );

                $operator = ' LIKE ';
                if (is_string( $value ) || is_numeric( $value )) {
                    $value_operator = '%'.$value.'%';
                }

                if (count( $get_operator_minor_equal ) > 1) {
                    $key            = $get_operator_minor_equal[0];
                    $operator       = '<=';
                    $value_operator = $value;
                } elseif (count( $get_operator_major_equal ) > 1) {
                    $key            = $get_operator_major_equal[0];
                    $operator       = '>=';
                    $value_operator = $value;
                } elseif (count( $get_operator_minor ) > 1) {
                    $key            = $get_operator_minor[0];
                    $operator       = '<';
                    $value_operator = $value;

                } elseif (count( $get_operator_major ) > 1) {
                    $key            = $get_operator_major[0];
                    $operator       = '>';
                    $value_operator = $value;
                } elseif (count( $get_operator_equal ) > 1) {
                    $key            = $get_operator_equal[0];
                    $operator       = '=';
                    $value_operator = $value;
                }

                //there are join?
                //Estoy haciendo que cuando se pase una busqueda ex cfpatient.patientId se percate que tiene que hacer un join.
                if (count( explode( '&', $key ) ) > 1) { //AND
                    $operation_and = true;
                    $operation_or  = false;
                    $key           = explode( '&', $key )[1];
                } elseif (count( explode( '|', $key ) ) > 1) {
                    $operation_or  = true; //OR
                    $operation_and = false; //OR
                    $key           = explode( '|', $key )[1];
                } else {
                    //By default and
                    $operation_and = true;
                    $operation_or  = false;
                }

                $join       = explode( '.', $key );
                $join_count = count( $join );
                if ($join_count > 1) {
                    //To verify that not exist key the same join.
                    $i = 0;
                    for (; $i < $join_count - 1; $i++) {
                        if ( ! array_key_exists( $join[$i], $join_name )) {
                            $join_name[$join[$i]] = $join[$i];
                            if ($i === 0) {
                                $qb_count->join( 'entity.'.$join[0], $join[0] );
                            } else {
                                $qb_count->join( $join[$i - 1].'.'.$join[$i], $join[$i] );
                            }
                        }
                    }
                    if ($operation_and === true) {
                        $qb_count = $qb_count->andWhere(
                            $join[$i - 1].'.'.$join[$i].' '.$operator.' '.':valueand'.(string) $iterator_search.$join[$i]
                        );
                        $qb_count->setParameter( 'valueand'.(string) $iterator_search.$join[$i], $value_operator );
                    } elseif ($operation_or === true) {
                        $qb_count = $qb_count->orWhere(
                            $join[$i - 1].'.'.$join[$i].' '.$operator.' '.':valueor'.(string) $iterator_search.$join[$i]
                        );
                        $qb_count->setParameter( 'valueor'.(string) $iterator_search.$join[$i], $value_operator );
                    }

                } else {
                    if ($operation_and === true) {
                        $qb_count = $qb_count->andWhere(
                            'entity.'.$key.' '.$operator.' '.':value'.(string) $iterator_search.$key
                        );
                        $qb_count->setParameter( 'value'.(string) $iterator_search.$key, $value_operator );
                    } elseif ($operation_or === true) {
                        $qb_count = $qb_count->orWhere(
                            'entity.'.$key.' '.$operator.' '.':value'.(string) $iterator_search.$key
                        );
                        $qb_count->setParameter( 'value'.(string) $iterator_search.$key, $value_operator );
                    }
                }
                $iterator_search++;
            }
        }
        $count = $qb_count->getQuery()->getSingleScalarResult();

        //Do Query
        $qb = $em->createQueryBuilder();
        $qb->select( 'entity' )->from( $this->getEntityName(), 'entity' );

        if (is_array( $criteria ) && count( $criteria ) > 0) {
            $join_name       = [ ];
            $operation_and   = null;
            $operation_or    = null;
            $iterator_search = 0;
            foreach ($criteria as $key => $value) {
                //get operator <, > or =
                $get_operator_minor       = explode( '<', $key );
                $get_operator_minor_equal = explode( '<=', $key );
                $get_operator_major       = explode( '>', $key );
                $get_operator_major_equal = explode( '>=', $key );
                $get_operator_equal       = explode( '=', $key );

                $operator = ' LIKE ';
                if (is_string( $value ) || is_numeric( $value )) {
                    $value_operator = '%'.$value.'%';
                }

                if (count( $get_operator_minor_equal ) > 1) {
                    $key            = $get_operator_minor_equal[0];
                    $operator       = '<=';
                    $value_operator = $value;
                } elseif (count( $get_operator_major_equal ) > 1) {
                    $key            = $get_operator_major_equal[0];
                    $operator       = '>=';
                    $value_operator = $value;
                } elseif (count( $get_operator_minor ) > 1) {
                    $key            = $get_operator_minor[0];
                    $operator       = '<';
                    $value_operator = $value;

                } elseif (count( $get_operator_major ) > 1) {
                    $key            = $get_operator_major[0];
                    $operator       = '>';
                    $value_operator = $value;
                } elseif (count( $get_operator_equal ) > 1) {
                    $key            = $get_operator_equal[0];
                    $operator       = '=';
                    $value_operator = $value;
                }

                //there are join?
                //Estoy haciendo que cuando se pase una busqueda ex cfpatient.patientId se percate que tiene que hacer un join.
                if (count( explode( '&', $key ) ) > 1) { //AND
                    $operation_and = true;
                    $operation_or  = false;
                    $key           = explode( '&', $key )[1];
                } elseif (count( explode( '|', $key ) ) > 1) {
                    $operation_or  = true; //OR
                    $operation_and = false; //OR
                    $key           = explode( '|', $key )[1];
                } else {
                    //By default and
                    $operation_and = true;
                    $operation_or  = false;
                }

                $join       = explode( '.', $key );
                $join_count = count( $join );
                if ($join_count > 1) {
                    //To verify that not exist key the same join.
                    $i = 0;
                    for (; $i < $join_count - 1; $i++) {
                        if ( ! array_key_exists( $join[$i], $join_name )) {
                            $join_name[$join[$i]] = $join[$i];
                            if ($i === 0) {
                                $qb->join( 'entity.'.$join[0], $join[0] );
                            } else {
                                $qb->join( $join[$i - 1].'.'.$join[$i], $join[$i] );
                            }
                        }
                    }
                    if ($operation_and === true) {
                        $qb = $qb->andWhere(
                            $join[$i - 1].'.'.$join[$i].' '.$operator.' '.':valueand'.(string) $iterator_search.$join[$i]
                        );
                        $qb->setParameter( 'valueand'.(string) $iterator_search.$join[$i], $value_operator );
                    } elseif ($operation_or === true) {
                        $qb = $qb->orWhere(
                            $join[$i - 1].'.'.$join[$i].' '.$operator.' '.':valueor'.(string) $iterator_search.$join[$i]
                        );
                        $qb->setParameter( 'valueor'.(string) $iterator_search.$join[$i], $value_operator );
                    }

                } else {
                    if ($operation_and === true) {
                        $qb = $qb->andWhere( 'entity.'.$key.' '.$operator.' '.':value'.(string) $iterator_search.$key );
                        $qb->setParameter( 'value'.(string) $iterator_search.$key, $value_operator );
                    } elseif ($operation_or === true) {
                        $qb = $qb->orWhere( 'entity.'.$key.' '.$operator.' '.':value'.(string) $iterator_search.$key );
                        $qb->setParameter( 'value'.(string) $iterator_search.$key, $value_operator );
                    }
                }
                $iterator_search++;
            }
        }
        if ($orderBy !== null) {
            $qb = $qb->orderBy( 'entity.'.$orderBy[0], $orderBy[1] );
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
     * Search with OR conditions.
     *
     * You can search intro relations, for ex:
     * entity cfUser:
     * username
     * password
     * cfPerson
     *
     * entity cfPerson:
     * firstname
     * lastname
     * personalId
     *
     * and the query by criteria:
     *
     * $criteria = [ 'username' => 'john', 'cfPerson.firstname' => 'john', 'cfPerson.personalId' => 'john' ];
     *
     *
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
        $em   = $this->getEntityManager();
        $expr = $em->getExpressionBuilder();

        //get Count
        $qb_count = $em->createQueryBuilder();
        $qb_count->select( 'COUNT(entity.id)' )->from( $this->getEntityName(), 'entity' );
        if (is_array( $criteria ) && count( $criteria ) > 0) {
            $join_name = [ ];
            foreach ($criteria as $key => $value) {
                //there are join?
                //Estoy haciendo que cuando se pase una busqueda ex cfpatient.patientId se percate que tiene que hacer un join.
                $join = explode( '.', $key );
                if (count( $join ) > 1) {
                    //To verify that not exist key the same join.
                    if ( ! array_key_exists( $join[0], $join_name )) {
                        $join_name[$join[0]] = $join[0];
                        $qb_count->join( 'entity.'.$join[0], $join[0] );
                    }
                    $qb_count = $qb_count->orWhere( $join[0].'.'.$join[1].' LIKE '.':value'.$join[1] );
                    $qb_count->setParameter( 'value'.$join[1], '%'.$value.'%' );
                } else {
                    $qb_count = $qb_count->orWhere( 'entity.'.$key.' LIKE '.':value'.$key );
                    $qb_count->setParameter( 'value'.$key, '%'.$value.'%' );
                }
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
            $join_name = [ ];
            foreach ($criteria as $key => $value) {
                //there are join?
                //Estoy haciendo que cuando se pase una busqueda ex cfpatient.patientId se percate que tiene que hacer un join.
                $join = explode( '.', $key );
                if (count( $join ) > 1) {
                    //To verify that not exist key the same join.
                    if ( ! array_key_exists( $join[0], $join_name )) {
                        $join_name[$join[0]] = $join[0];
                        $qb->join( 'entity.'.$join[0], $join[0] );
                    }
                    $qb = $qb->orWhere( $join[0].'.'.$join[1].' LIKE '.':value'.$join[1] );
                    $qb->setParameter( 'value'.$join[1], '%'.$value.'%' );
                } else {
                    $qb = $qb->orWhere( 'entity.'.$key.' LIKE '.':value'.$key );
                    $qb->setParameter( 'value'.$key, '%'.$value.'%' );
                }
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
     * Search with AND conditions.
     *
     * You can search intro relations, for ex:
     * entity cfUser:
     * username
     * password
     * cfPerson
     *
     * entity cfPerson:
     * firstname
     * lastname
     * personalId
     *
     * and the query by criteria:
     *
     * $criteria = [ 'username' => 'john', 'cfPerson.firstname' => 'john', 'cfPerson.personalId' => 'john' ];
     *
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
            $join_name = [ ];
            foreach ($criteria as $key => $value) {
                //there are join?
                //Estoy haciendo que cuando se pase una busqueda ex cfpatient.patientId se percate que tiene que hacer un join.
                $join = explode( '.', $key );
                if (count( $join ) > 1) {
                    //To verify that not exist key the same join.
                    if ( ! array_key_exists( $join[0], $join_name )) {
                        $join_name[$join[0]] = $join[0];
                        $qb_count->join( 'entity.'.$join[0], $join[0] );
                    }
                    $qb_count = $qb_count->andWhere( $join[0].'.'.$join[1].' LIKE '.':value'.$join[1] );
                    $qb_count->setParameter( 'value'.$join[1], '%'.$value.'%' );
                } else {
                    $qb_count = $qb_count->andWhere( 'entity.'.$key.' LIKE '.':value'.$key );
                    $qb_count->setParameter( 'value'.$key, '%'.$value.'%' );
                }
            }
        }
        $count = $qb_count->getQuery()->getSingleScalarResult();

        //Do Query
        $qb = $em->createQueryBuilder();
        $qb->select( 'entity' )->from( $this->getEntityName(), 'entity' );

        if (is_array( $criteria ) && count( $criteria ) > 0) {
            $join_name = [ ];
            foreach ($criteria as $key => $value) {
                //there are join?
                //Estoy haciendo que cuando se pase una busqueda ex cfpatient.patientId se percate que tiene que hacer un join.
                $join = explode( '.', $key );
                if (count( $join ) > 1) {
                    //To verify that not exist key the same join.
                    if ( ! array_key_exists( $join[0], $join_name )) {
                        $join_name[$join[0]] = $join[0];
                        $qb->join( 'entity.'.$join[0], $join[0] );
                    }
                    $qb = $qb->andWhere( $join[0].'.'.$join[1].' LIKE '.':value'.$join[1] );
                    $qb->setParameter( 'value'.$join[1], '%'.$value.'%' );
                } else {
                    $qb = $qb->andWhere( 'entity.'.$key.' LIKE '.':value'.$key );
                    $qb->setParameter( 'value'.$key, '%'.$value.'%' );
                }
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