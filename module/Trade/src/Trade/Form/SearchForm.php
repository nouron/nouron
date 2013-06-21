<?php
namespace Trade\Form;

use \Trade\Form\AbstractTradeForm;

class SearchForm extends AbstractTradeForm
{
    public function __construct($search = 'resources', $items)
    {
        if (empty($search)) return false;

        parent::__construct('search-'.$search);

        $this->setAttribute('method', 'post');
        $this->setAttribute('name', 'searchForm');

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'direction',
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