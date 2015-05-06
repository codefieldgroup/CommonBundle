<?php
namespace Cf\CommonBundle\Listener;

use Symfony\Component\Security\Core\SecurityContext;
use \Doctrine\Common\Util\Inflector as Inflector;

class MiscellaneousListener
{
    /**
     * @param $new
     * @param $old
     * @param bool $infinite
     *
     * @return array|null
     */
    static public function mergeArray($new, &$old, $infinite = false){
        if(count($new) > 0 && is_array($new)){
            foreach ($new as $key => $value){
                if (is_array($old[ $key ]) && is_array($old) && $infinite){
                    self::merge_array_new_intro_old($value, $old[ $key ]);
                }else{
                    $old[ $key ] = $value;
                }
            }
            return $old;
        }else{
            return null;
        }
    }


    /**
     * Merge or set array intro object.
     *
     * @param $object
     * @param $arguments
     *
     * @return mixed
     */
    static public function bindParameters(&$object, $arguments)
    {
        if (is_array($arguments) && count($arguments) > 0) {
            foreach ($arguments as $property => $argument) {
                $local_function = sprintf('set%s', Inflector::camelize($property));
                if (method_exists($object, $local_function)) {
                    $object->$local_function($argument);
                    continue;
                }

                $local_function = sprintf('set%s', ucwords($property));
                if (method_exists($object, $local_function)) {
                    $object->$local_function($argument);
                    continue;
                }

                $local_function = sprintf('set%s', $property);
                if (method_exists($object, $local_function)) {
                    $object->$local_function($argument);
                    continue;
                }

            }
        }

        return $object;
    }
}