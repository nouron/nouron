<?php
namespace Trade\Entity;

use Nouron\Model\EntityInterface;

class Technology implements EntityInterface
{
    public $colony_id;
    public $direction;
    public $tech_id;
    public $amount;
    public $price;
    public $restriction;

    public function toArray()
    {
        return array(
            'colony_id' => $this->colony_id,
            'direction' => $this->direction,
            'tech_id' => $this->tech_id,
            'amount' => $this->amount,
            'price' => $this->price,
            'restriction' => $this->restriction
        );
    }
}

