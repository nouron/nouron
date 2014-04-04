<?php
namespace Trade\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;

abstract class AbstractTradeForm extends Form implements InputFilterProviderInterface
{
    public function getSelectOptions($items)
    {
        $options = array();
        foreach ($items as $id => $item) {
            if (!isset($item['is_tradeable']) || $item['is_tradeable'] == true) {
                $options[$id] = $item['name'];
            }
        }

        return $options;
    }

}