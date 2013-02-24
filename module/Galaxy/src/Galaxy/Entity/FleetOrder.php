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

    public function toArray()
    {
        return array(
            'tick' => $this->tick,
            'fleet_id' => $this->fleet_id,
            'order' => $this->order,
            'coordinates' => $this->coordinates,
            'data' => $this->data,
            'was_processed' => $this->was_processed,
            'has_notified' => $this->has_notified,
        );
    }
}

