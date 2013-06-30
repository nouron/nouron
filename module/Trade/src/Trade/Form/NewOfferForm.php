<?php
namespace Trade\Form;

use \Trade\Form\AbstractTradeForm;

class NewOfferForm extends AbstractTradeForm
{
    /**
     *
     * @param String $offerType 'resources' or 'technologies'
     * @param array  $items
     * @param int    $range OPTIONAL (default=0: only offers on own planet)
     */
    public function __construct($offerType = 'resources', $items, $range = 0)
    {
        if (empty($offerType)) return false;

        parent::__construct('offerType-'.$offerType);

        $this->setAttribute('method', 'post');
        $this->setAttribute('name', 'newOfferForm');
        $this->setAttribute('action', '/trade/add-offer');

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
                    0 => 'i search',
                    1 => 'i sell'
                )
            )
        ));

        $this->add(array(
            'type' => 'text',
            'name' => 'amount',
            'options' => array(
                'label' => 'units'
             ),
            'attributes' => array(
                'value' => '0'
            )
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'item_id',
            'options' => array(
                'id' => 'item_id',
                'value_options' => $this->getSelectOptions($items),
            )
        ));

        $options = array();
        for ($i=0; $i<=$range; $i++) {
            if ($i<2) {
                $options[$i] = ($i==1) ? "in this system" : "on this planet";
            } else {
                $options[$i] = $i." Systeme";
            }
        }
        # distance = $range * $system_size

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'range',
            'options' => array(
                'id' => 'range',
                'label' => 'range',
                'value_options' => $options,
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'create offer',
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
            'item_id' => array (
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