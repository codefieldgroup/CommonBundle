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

        if(!($currentDate instanceof \DateTime)){
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
     * @param $object
     * @param $arguments
     * @param bool $prepareAttributes
     *
     * @return mixed
     */

    public function bindParameters( &$object, $arguments, $prepareAttributes = false)
    {
        if ($prepareAttributes !== false){
            $em = $this->container->get( 'doctrine.orm.entity_manager' );

            $arguments = $em->getRepository(get_class($object))->prepareAttributes($arguments);
        }

        if (is_array( $arguments ) && count( $arguments ) > 0) {
            foreach ($arguments as $property => $argument) {
                $local_function = sprintf( 'set%s', Inflector::camelize( $property ) );
                $get_local_function = sprintf( 'get%s', Inflector::camelize( $property ) );
                if (method_exists( $object, $local_function )) {

                    if(is_object($object->$get_local_function())){
                        if( $object->$get_local_function() instanceof  \DateTime && !is_object($argument)){
                            $argument = new \DateTime($argument);
                        }
                    }
                    $object->$local_function( $argument );
                    continue;
                }

                $local_function = sprintf( 'set%s', ucwords( $property ) );
                if (method_exists( $object, $local_function )) {
                    echo $local_function;

                    $object->$local_function( $argument );
                    continue;
                }

                $local_function = sprintf( 'set%s', $property );
                if (method_exists( $object, $local_function )) {
                    echo $local_function;

                    $object->$local_function( $argument );
                    continue;
                }

            }
        }

        return $object;
    }
}