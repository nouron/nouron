<?php
namespace Trade\Form;

use Techtree\Entity\Technology;
use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;

class TechnologyFieldset extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('technology');

        $this->setHydrator(new ClassMethodsHydrator(false))
             ->setObject(new Technology());

        $this->setLabel('technology');

        $this->add(array(
            'name' => 'technology',
            'options' => array(
                'label' => 'technology'
            ),
            'attributes' => array(
                'required' => 'required'
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
            )
        );
    }
}
