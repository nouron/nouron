<?php
namespace Galaxy\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterProviderInterface;

class Fleet extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('fleet');
        $this->setAttribute('action', '/fleet');
        $this->setAttribute('method', 'post');
        $this->add(array(
                'name' => 'id',
                'attributes' => array(
                        'type' => 'hidden',
                        'value' => 0
                ),
        ));
        $this->add(array(
            'name' => 'fleet',
            'attributes' => array(
                'type' => 'input',
                'value' => 'Flotte'
            ),
        ));
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'Hinzufügen'
            ),
        ));
    }

    public function getInputFilterSpecification()
    {
        return array(
            'fleetname' => array (
                'required' => true,
                'filters' => array(
                    array(
                        'name' => 'StringTrim'
                    )
                ),
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'message' =>
                            "Bitte gib der Flotte einen Namen."
                        )
                    )
                )
            ),
        );
    }
}