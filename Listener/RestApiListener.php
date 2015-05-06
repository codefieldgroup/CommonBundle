<?php

namespace Cf\CommonBundle\Listener;


class RestApiListener
{

    /**
     * Build a rest api result.
     *
     * @param mixed $entities Result to show in frontend.
     * @param array $msg Type of result (success, error, warning) and Message to show in frontend. Ex. array('type', 'msg')
     * @param null $extra Extra data.
     * @param null $linked
     *
     * @return array
     */
    public function buildRestApi( $entities, $msg = null, $extra = null, $linked = null )
    {

        $return_array = [
            'meta'   => [
                'msg'   => $msg ? $msg : [ 'type' => 'none', 'text' => null ],
                'extra' => $extra,
            ],
            'linked' => $linked,
            'data'   => $entities,
        ];

        return $return_array;
    }
}