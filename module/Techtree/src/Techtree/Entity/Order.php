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

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

