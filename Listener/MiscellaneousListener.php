<?php
namespace Cf\CommonBundle\Listener;

use Symfony\Component\Security\Core\SecurityContext;
use \Doctrine\Common\Util\Inflector as Inflector;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MiscellaneousListener
{

    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct( ContainerInterface $container )
    {
        $this->container = $container;
    }


    /**
     *
     * @param $dateBirth \DateTime Date of birth
     * @param $currentDate \DateTime Now
     *
     * @return int
     */
    static public function calculateAge( $dateBirth, $currentDate = null )
    {
        if ( ! ( $dateBirth instanceof \DateTime )) {
            return null;
        }

        if ( ! ( $currentDate instanceof \DateTime )) {
            $currentDate = new \DateTime( 'now' );
        }


        return $currentDate->diff( $dateBirth )->y;
    }

    /**
     * @param $new
     * @param $old
     * @param bool $infinite
     *
     * @return array|null
     */
    static public function mergeArray( $new, &$old, $infinite = false )
    {
        if (count( $new ) > 0 && is_array( $new )) {
            foreach ($new as $key => $value) {
                if (is_array( $old[$key] ) && is_array( $old ) && $infinite) {
                    self::merge_array_new_intro_old( $value, $old[$key] );
                } else {
                    $old[$key] = $value;
                }
            }

            return $old;
        } else {
            return null;
        }
    }


    /**
     * Merge or set array intro object.
     *
     * Reference: http://localhost:8080/stackoverflow/20254144
     *
     * @param $object
     * @param $arguments
     * @param bool $prepareAttributes
     *
     * @return mixed
     */

    public function bindParameters( &$object, $arguments, $prepareAttributes = false )
    {
        //This condition convert o prepared arguments(relations, so on)
        if ($prepareAttributes !== false) {
            $em = $this->container->get( 'doctrine.orm.entity_manager' );

            $arguments = $em->getRepository( get_class( $object ) )->prepareAttributes( $arguments );
        }

        if (is_array( $arguments ) && count( $arguments ) > 0) {
            foreach ($arguments as $property => $argument) {
                $local_function     = sprintf( 'set%s', Inflector::camelize( $property ) );
                $get_local_function = sprintf( 'get%s', Inflector::camelize( $property ) );
                if (method_exists( $object, $local_function )) {
                    if (is_string( $argument )) {   //Check if get function or $argument are DateTime Type
                        if ($object->$get_local_function() instanceof \DateTime) {
                            $argument = new \Datetime( $argument );
                        } elseif ($date_parse = date_parse( $argument )) {
                            if (array_key_exists( 'error_count', $date_parse ) && $date_parse['error_count'] === 0) {

                                $regDate = '/(\d{4})-(\d{2})-(\d{2})/';
                                $regTime = '/(\d{2}):(\d{2}):(\d{2})/';
                                if (preg_match( $regDate, $argument, $result ) || preg_match( $regTime, $argument, $result )
                                ) {
                                    $argument = new \Datetime( $argument );
                                }
                            }
                        }
                    }
                    $object->$local_function( $argument );
                    continue;
                }

                $local_function     = sprintf( 'set%s', ucwords( $property ) );
                $get_local_function = sprintf( 'get%s', ucwords( $property ) );
                if (method_exists( $object, $local_function )) {
                    if (is_string( $argument )) {   //Check if get function or $argument are DateTime Type
                        if ($object->$get_local_function() instanceof \DateTime) {
                            $argument = new \Datetime( $argument );
                        } elseif ($date_parse = date_parse( $argument )) {
                            if (array_key_exists( 'error_count', $date_parse ) && $date_parse['error_count'] === 0) {
                                $argument = new \Datetime( $argument );
                            }
                        }
                    }
                    $object->$local_function( $argument );
                    continue;
                }

                $local_function     = sprintf( 'set%s', $property );
                $get_local_function = sprintf( 'get%s', $property );
                if (method_exists( $object, $local_function )) {
                    if (is_string( $argument )) {   //Check if get function or $argument are DateTime Type
                        if ($object->$get_local_function() instanceof \DateTime) {
                            $argument = new \Datetime( $argument );
                        } elseif ($date_parse = date_parse( $argument )) {
                            if (array_key_exists( 'error_count', $date_parse ) && $date_parse['error_count'] === 0) {
                                $argument = new \Datetime( $argument );
                            }
                        }
                    }
                    $object->$local_function( $argument );
                    continue;
                }
            }
        }

        return $object;
    }
}