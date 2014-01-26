<?php
namespace Trade\Form;

use \Trade\Form\AbstractTradeForm;

class NewOfferForm extends AbstractTradeForm
{
    /**
     *
     * @param String $offerType 'resources' or 'researches'
     * @param array  $items
     * @param int    $range OPTIONAL (default=0: only offers on own planet)
     */
    public function __construct($offerType = 'resources', $items, $range = 0)
    {
        if (empty($offerType)) return false;

        parent::__construct('offerType-'.$offerType);

        $this->setAttribute('method', 'post');
        $this->setAttribute('name', 'newOfferForm');
        if ($offerType == 'resources') {
            $this->setAttribute('action', '/trade/add-resource-offer');
        } else {
            $this->setAttribute('action', '/trade/add-research-offer');
        }

        $this->add(array(
            'type' => 'hidden',
            'name' => 'form_name',
            'attributes' => array(
                'value' => 'new_offer'
            )
        ));

        $this->add(array(
            'type' => 'hidden',
            'name' => 'item_type',
            'attributes' => array(
                'value' => $offerType
            )
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'direction',
            'options' => array(
                #'label' => 'offerType',
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
                #'label' => 'units'
            ),
            'attributes' => array(
                'id' => 'amount',
                'class' => 'input-small',
                'placeholder' => 'units'
            ),
//            'twb' => array(
//                'prepend' => array(
//                    'type' => 'text',
//                    'text' => 'Prepended text'
//                ),
//                'append' => array(
//                    'type' => 'icon',
//                    'icon' => 'glyphicon glyphicon-enveloppe' //icon class
//                )
//            )
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

        $this->add(array(
            'type' => 'text',
            'name' => 'price',
            'options' => array(
                #'label' => 'price'
             ),
            'attributes' => array(
                'id' => 'price',
                'class' => 'input-small',
                'placeholder' => 'price per unit'
            )
        ));

        # distance = $range * $system_size

#        $this->add(array(
#            'type' => 'Zend\Form\Element\Select',
#            'name' => 'range',
#            'options' => array(
#                'id' => 'range',
#                'value_options' => $options,
#            )
#        ));

#        $this->add(array(
#            'type' => 'Zend\Form\Element\Select',
#            'name' => 'restriction',
#            'options' => array(
#                'id' => 'restriction',
#                #'label' => 'restriction'
#                'value_options' => array(
#                    0 => 'no restriction',
#                    1 => 'only my group',
#                    2 => 'only my faction',
#                    3 => 'only my race',
#                )
#             ),
#//             'attributes' => array(
#//                 'disabled' => 'disabled'
#//             )
#        ));


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
