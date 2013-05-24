<?php
namespace Galaxy\Entity;

use Nouron\Model\EntityInterface;

class FleetOrder implements EntityInterface
{
    public $tick;
    public $fleet_id;
    public $order;
    public $coordinates;
    public $data;
    public $was_processed;
    public $has_notified;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

