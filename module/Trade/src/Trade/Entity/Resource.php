<?php
namespace Trade\Entity;

use Nouron\Model\EntityInterface;

class Resource implements EntityInterface
{
    public $colony_id;
    public $direction;
    public $resource_id;
    public $amount;
    public $price;
    public $restriction;

    public function toArray()
    {
        return array(
            'colony_id' => $this->colony_id,
            'direction' => $this->direction,
            'resource_id' => $this->resource_id,
            'amount' => $this->amount,
            'price' => $this->price,
            'restriction' => $this->restriction
        );
    }
}

