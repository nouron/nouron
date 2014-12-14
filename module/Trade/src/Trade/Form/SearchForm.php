<?php
namespace Trade\Form;

use \Trade\Form\AbstractTradeForm;

class SearchForm extends AbstractTradeForm
{
    /**
     * @param String $search  Search item type
     * @param array  $items   Items to fill select options
     * @param int    $range   OPTIONAL (default=0: only offers on own planet)
     */
    public function __construct($search = 'resources', $items, $range=0)
    {
        if (empty($search)) return false;

        parent::__construct('search-' . $search);

        $this->setAttribute('method', 'post');
        $this->setAttribute('name', 'searchForm');

        $this->add(array(
            'type' => 'hidden',
            'name' => 'form_name',
            'attributes' => array(
                'value' => 'search'
            )
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'direction',
            'options' => array(
                'id' => 'direction',
                'value_options' => array(
                    1 => 'i search',
                    0 => 'i sell'
                )
            )
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'item_id',
            'options' => array(
                'id' => 'item_id',
                'value_options' => $this->getSelectOptions($items),
                'empty_option'  => 'all'
            ),
            'attributes' => array(
                'value' => '0'
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
                'value_options' => $options,
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
            'item_id' => array (
                'required' => false,
                'filters' => array(
                    array(
                        'name' => 'StringTrim',
                    )
                ),
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