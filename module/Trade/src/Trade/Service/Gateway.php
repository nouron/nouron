<?php
namespace Trade\Service;

class Gateway extends \Nouron\Service\Gateway
{
    public function getTechnologies()
    {
        return $this->getTable('technology')->fetchAll();
    }

    public function getResources()
    {
        return $this->getTable('resources')->fetchAll();
    }
}