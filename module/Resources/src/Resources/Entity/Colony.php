<?php
namespace Resources\Entity;

use Nouron\Model\EntityInterface;

class Colony implements EntityInterface
{
    public $resource_id;
    public $colony_id;
    public $amount;

    public function toArray()
    {
        return array(
            'resource_id' => $this->resource_id,
            'colony_id' => $this->colony_id,
            'amount' => $this->amount,
        );
    }
}

