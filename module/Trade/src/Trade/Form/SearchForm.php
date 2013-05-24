<?php
namespace Trade\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterProviderInterface;

class SearchForm extends Form implements InputFilterProviderInterface
{
    public function getSelectOptions($techs)
    {
        $options = array();
        foreach ($techs as $id => $tech) {
            if ($tech['tradeable'] == true) {
                $options[$id] = $tech['name'];
            }
        }
        return $options;
    }

    public function __construct($search = 'technologies', $techs)
    {
        if (empty($search)) return false;

        parent::__construct('search-'.$search);

        $this->setAttribute('method', 'post');

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'direction',
            'options' => array(
                'id' => 'direction',
                'label' => 'direction',
                'value_options' => array(
                    0 => 'trade_iSearch',
                    1 => 'trade_iSell'
                )
            ),
            'attributes' => array(
                'value' => '0'
            )
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'technology',
            'options' => array(
                'id' => 'technology',
                'label' => 'technology',
                'value_options' => $this->getSelectOptions($techs),
                'empty_option'  => '--- please choose ---'
            ),
            'attributes' => array(
                'value' => '0'
            )
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'range',
            'options' => array(
                'id' => 'range',
                'label' => 'range',
                'value_options' => array(
                     0 => 'planet',
                     1 => 'system',
                     2 => 'galaxy'
                 ),
            ),
            'attributes' => array(
                'value' => '0'
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
            'direction' => array (
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
                            'message' => "Bitte gib den Angebotstyp an."
                        )
                    ),
                )
            ),
            'item' => array (
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
                            'message' => "Wähle das Handelsobjekt."
                        )
                    ),
                )
            ),
            'range' => array (
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
                            'message' => "Wähle den Angebotsbereich."
                        )
                    ),
                )
            ),
        );
    }
}