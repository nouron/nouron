<?php
namespace INNN\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterProviderInterface;

class Message extends Form implements InputFilterProviderInterface
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct('message');
        $this->setAttribute('action', '/innn/message/new');
        $this->setAttribute('method', 'post');
        $this->add(array(
            'name' => 'id',
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));
        $this->add(array(
            'name' => 'recipient',
            'attributes' => array(
                'type' => 'input',
                'class' => 'input-xxlarge'
            ),
            'options' => array(
                'label' => 'recipient',
            )
        ));
        $this->add(array(
            'name' => 'subject',
            'attributes' => array(
                'type' => 'input',
                'class' => 'input-xxlarge'
            ),
            'options' => array(
                'label' => 'subject',
            )
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'mood',
            'attributes' =>  array(
                'id' => 'mood',
                'options' => array(
                    # key is value stored in db, value is translated for output
                    'mood_friendly' => 'mood_friendly',
                    'mood_neutral'  => 'mood_neutral',
                    'mood_aggressive' => 'mood_aggressive',
                    'mood_begging' => 'mood_begging',
                    'mood_supliant' => 'mood_supliant',
                    'mood_humble' => 'mood_humble',
                    'mood_factual' => 'mood_factual',
                    'mood_frosty' => 'mood_frosty',
                ),
            ),
            'options' => array(
                'label' => 'mood',
            ),
        ));
        $this->add(array(
            'name' => 'text',
            'attributes' => array(
                'type' => 'textarea',
                'class' => 'input-xxlarge'
             ),
            'options' => array(
                'label' => 'message',
            )
        ));
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'send',
                'class' => 'btn btn-primary'
            ),
        ));
    }

    /**
     * (non-PHPdoc)
     * @see \Zend\InputFilter\InputFilterProviderInterface::getInputFilterSpecification()
     */
    public function getInputFilterSpecification()
    {
        return array(
            'recipient' => array (
                'required' => true,
                'filters' => array(
                    array(
                        'name' => 'StringTrim',
                    )
                ),
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'message' => "Bitte gib einen Spielernamen an."
                        )
                    ),
                )
            ),
            'subject' => array (
                'required' => true,
                'filters' => array(
                    array(
                        'name' => 'StringTrim',
                    )
                ),
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'message' => "Bitte gib einen Betreff an."
                        )
                    ),
                )
            ),
            'text' => array (
                'required' => true,
                'filters' => array(
                    array(
                        'name' => 'StringTrim',
                    )
                ),
                'validators' => array(
                    array(
                        'name' => 'NotEmpty',
                        'options' => array(
                            'message' => "Bitte gib einen Text an."
                        )
                    ),
                )
            ),
        );
    }
}