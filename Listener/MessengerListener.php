<?php

namespace Cf\CommonBundle\Listener;

use Symfony\Component\DependencyInjection\ContainerInterface;

class MessengerListener
{
    private $container;

    /**
     * @var array
     *
     */
    public $errors;

    /**
     * @var array
     */
    public $success;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {

        $this->container = $container;

        //        $errorr = [ 'dddd' => sprintf($this,'sd'),'sdfdsf' ];
        $this->errors = [
            /*Errors authentication*/
            403 => [
                'type' => 'notification',
                'text' => 'code_401_access_deny',
                'image' => '/bundles/cfsclinic/images/loading.gif',
                'class_name' => 'cualquie cosa',
                'itype' => 'Notificación',
                'show_image' => false,
            ],
            /*Errors from server*/
            //            500  => ['type' => 'error', 'text' => sprintf('code(500) -%s Error al establecer conexión con la Base de Datos.',$t)],
            500 => ['type' => 'error', 'text' => 'Code(500) - Error al establecer conexión con la Base de Datos.'],
            501 => ['type' => 'error', 'text' => 'Code(501) - Error desconocido.'],
            /*Specific Errors*/
            1000 => ['type' => 'error', 'text' => 'Code(1000) - Error no encontrado.'],
            1001 => [
                'type' => 'warning',
                'text' => 'Code(1001) - El elemento no fue encontrado.',
                'image' => '/bundles/cfsclinic/images/loading.gif',
                'class_name' => 'cualquie cosa',
                'itype' => 'Advertencia',
                'show_image' => false,
            ],
            1002 => [
                'type' => 'warning',
                'text' => 'Code(1002) - Nombre duplicado.',
                'image' => '/bundles/cfsclinic/images/loading.gif',
                'class_name' => 'cualquie cosa',
                'itype' => 'Advertencia',
                'show_image' => false,
            ],
            1003 => [
                'type' => 'warning',
                'text' => 'Code(1003) - Debe especificar un nombre.',
                'image' => '/bundles/cfsclinic/images/loading.gif',
                'class_name' => 'cualquie cosa',
                'itype' => 'Advertencia',
                'show_image' => false,
            ],
            1004 => [
                'type' => 'warning',
                'text' => 'Code(1004) - Debe suministrar un ID correcto.',
                'image' => '/bundles/cfsclinic/images/loading.gif',
                'class_name' => 'cualquie cosa',
                'itype' => 'Advertencia',
                'show_image' => false,
            ],
        ];

        $this->success = [
            2000 => ['type' => 'success', 'text' => 'Success no encontrado.'],
            2001 => [
                'type' => 'success',
                'text' => 'Creado satisfactoriamente.',
                'image' => '/bundles/cfsclinic/images/loading.gif',
                'class_name' => 'cualquie cosa',
                'itype' => 'Satisfactorio',
                'show_image' => false,
            ],
            2002 => ['type' => 'success', 'text' => 'Actualizado satisfactoriamente.'],
            2003 => ['type' => 'success', 'text' => 'Eliminado satisfactoriamente.'],
            2004 => ['type' => 'success', 'text' => 'Cancelado satisfactoriamente.'],

        ];
    }


    /**
     * @param $key
     * @param $msg
     * @param $replaceMsg
     *
     * @return array
     */
    public function getError($key, $msg = null, $replaceMsg = false)
    {
        if ($key !== null && array_key_exists($key, $this->errors)) {
            $error = (array)$this->errors[$key];
            if ($msg === null && $replaceMsg === false) {
                $error['text'] = $this->container->get('translator')->trans($error['text']);
            } elseif ($msg !== null && $replaceMsg === false) {
                $error['text'] = $this->container->get('translator')->trans($error['text']).' '.$this->container->get(
                        'translator'
                    )->trans($msg);
            } elseif ($msg !== null && $replaceMsg === true) {
                $error['text'] = $this->container->get('translator')->trans($msg);
            }

            return (array)$error;
        } else {
            return (array)$this->errors[1000];
        }
    }

    /**
     * @param $key
     *
     * @return array
     */
    public function getSuccess($key)
    {
        if ($key !== null && array_key_exists($key, $this->success)) {
            return (array)$this->success[$key];
        } else {
            return (array)$this->success[2000];
        }

    }

    /**
     * @param $errors
     *
     * @return array
     */
    public function parseErrorsByValidator($errors)
    {
        if (count($errors) > 0) { //Exist validations errors
            $msg_text = '';
            foreach ($errors as $key => $error) {
                if ($msg_text !== '') {
                    $msg_text .= '<br/>';
                }
                //@Example: $msg_text .= $error->getPropertyPath() . ':' . $error->getMessage();
                $msg_text .= $error->getMessage();
            }

            return ['type' => 'error', 'text' => $msg_text];
        } else {
            return null;
        }
    }
}