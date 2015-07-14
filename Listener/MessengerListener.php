<?php

namespace Cf\CommonBundle\Listener;


class MessengerListener
{
    /**
     * @var array
     *
     */
    public $errors;

    /**
     * @var array
     */
    public $success;

    function __construct()
    {
        //        $errorr = [ 'dddd' => sprintf($this,'sd'),'sdfdsf' ];
        $this->errors = [
            /*Errors authentication*/
            403  => [ 'type' => 'error', 'text' => 'Code(403) - Acceso denegado - Usted no cuenta con los permisos necesarios.' ],
            /*Errors from server*/
            //            500  => ['type' => 'error', 'text' => sprintf('code(500) -%s Error al establecer conexión con la Base de Datos.',$t)],
            500  => [ 'type' => 'error', 'text' => 'Code(500) - Error al establecer conexión con la Base de Datos.' ],
            501  => [ 'type' => 'error', 'text' => 'Code(501) - Error desconocido.' ],
            /*Specific Errors*/
            1000 => [ 'type' => 'error', 'text' => 'Code(1000) - Error no encontrado.' ],
            1001 => [ 'type' => 'error', 'text' => 'Code(1001) - El elemento no fue encontrado.' ],
            1002 => [ 'type' => 'error', 'text' => 'Code(1002) - Nombre duplicado.' ],
            1003 => [ 'type' => 'error', 'text' => 'Code(1003) - Debe especificar un nombre.' ],
            1004 => [ 'type' => 'error', 'text' => 'Code(1004) - Debe suministrar un ID correcto.' ]
        ];

        $this->success = [
            2000 => [ 'type' => 'success', 'text' => 'Success no encontrado.' ],
            2001 => [ 'type' => 'success', 'text' => 'Creado satisfactoriamente.' ],
            2002 => [ 'type' => 'success', 'text' => 'Actualizado satisfactoriamente.' ],
            2003 => [ 'type' => 'success', 'text' => 'Eliminado satisfactoriamente.' ],
            2004 => [ 'type' => 'success', 'text' => 'Cancelado satisfactoriamente.' ]

        ];
    }


    /**
     * @param $key
     *
     * @return array
     */
    public function getError( $key )
    {
        if ($key !== null && array_key_exists( $key, $this->errors )) {
            return (array) $this->errors[$key];
        } else {
            return (array) $this->errors[1000];
        }
    }

    /**
     * @param $key
     *
     * @return array
     */
    public function getSuccess( $key )
    {
        if ($key !== null && array_key_exists( $key, $this->success )) {
            return (array) $this->success[$key];
        } else {
            return (array) $this->success[2000];
        }

    }

    /**
     * @param $errors
     *
     * @return array
     */
    public function parseErrorsByValidator( $errors )
    {
        if (count( $errors ) > 0) { //Exist validations errors
            $msg_text = '';
            foreach ($errors as $key => $error) {
                if ($msg_text !== '') {
                    $msg_text .= '<br/>';
                }
                //@Example: $msg_text .= $error->getPropertyPath() . ':' . $error->getMessage();
                $msg_text .= $error->getMessage();
            }

            return [ 'type' => 'error', 'text' => $msg_text ];
        } else {
            return null;
        }
    }
}