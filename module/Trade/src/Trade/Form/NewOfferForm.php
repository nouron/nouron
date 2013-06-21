<?php
namespace Trade\Form;


class NewOfferForm extends \Trade\Form\AbstractTradeForm
{

    public function __construct($offerType = 'resources', $items)
    {
        if (empty($offerType)) return false;

        parent::__construct('offerType-'.$offerType);

        $this->setAttribute('method', 'post');
        $this->setAttribute('name', 'newOfferForm');

        $this->add(array(
            'type' => 'hidden',
            'name' => 'form_name',
            'attributes' => array(
                'value' => 'new_offer'
            )
        ));

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
            'type' => 'text',
            'name' => 'amount',
            'label' => 'amount',
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'item_id',
            'options' => array(
                'id' => 'item_id',
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
            'amount' => array (
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
                            'message' => "Wähle die Menge."
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