<?php
namespace Techtree\Entity;

use Nouron\Model\EntityInterface;

class Order implements EntityInterface
{
    public $tick;
    public $colony_id;
    public $tech_id;
    public $order;
    public $ap_ordered;
    public $is_final_step;
    public $was_progressed;
    public $has_notified;

    public function toArray()
    {
        return array(
            'tick' => $this->tick,
            'colony_id' => $this->colony_id,
            'tech_id' => $this->tech_id,
            'order' => $this->order,
            'ap_ordered' => $this->ap_ordered,
            'is_final_step' => $this->is_final_step,
            'was_progressed' => $this->was_progressed,
            'has_notified' => $this->has_notified,
        );
    }
}

