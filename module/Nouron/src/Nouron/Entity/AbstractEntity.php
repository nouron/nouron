<?php
namespace Nouron\Entity;

abstract class AbstractEntity implements EntityInterface
{
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function exchangeArray(array $array)
    {
        foreach($array as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $method = 'set'.ucfirst($key);
            if (!method_exists($this, $method)) {
                continue;
            }
            $this->$method($value);
        }
    }
}
