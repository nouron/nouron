<?php
namespace Trade\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterProviderInterface;

class NewOfferForm extends Form implements InputFilterProviderInterface
{
    public function getSelectOptions($items)
    {
        $options = array();
        foreach ($items as $id => $item) {
            if (!isset($item['tradeable']) || $item['tradeable'] == true) {
                $options[$id] = $item['name'];
            }
        }
        return $options;
    }

    public function __construct($offerType = 'resources', $items)
    {
        if (empty($offerType)) return false;

        parent::__construct('offerType-'.$offerType);

        $this->setAttribute('method', 'post');
        $this->setAttribute('name', 'newOfferForm');

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'direction',
            'label' => 'offerType',
            'options' => array(
                'id' => 'direction',
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
            'name' => 'item',
            'options' => array(
                'id' => 'item',
                'value_options' => $this->getSelectOptions($items),
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
                'value_options' => array(
                     0 => 'trade-on-this-planet',
                     1 => 'trade-in-this-system',
                     2 => 'trade-in-galaxy'
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