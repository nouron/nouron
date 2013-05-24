<?php
namespace Trade\Form;

use Techtree\Entity\Technology;
use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;

class SearchFieldset extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('technology');

        $this->setLabel('search');

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
                'label' => 'technology',
                'count' => 1,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => array(
                    'type' => 'Trade\Form\TechnologyFieldset'
                )
            )
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'range',
            'options' => array(
                'id' => 'range',
                'label' => 'range',
                'value_options' => array(
                    0 => 'trade_rangePlanet',
                    1 => 'trade_rangeSystem',
                    2 => 'trade_rangeGalaxy'
                )
            ),
            'attributes' => array(
                'value' => '0'
            )
        ));
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return array(
            'technology' => array(
                'required' => true,
            ),
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
