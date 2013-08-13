<?php
namespace Techtree\Entity;

use Nouron\Model\EntityInterface;

class Possession implements EntityInterface
{
    public $colony_id;
    public $tech_id;
    public $display_name;
    public $level;
    public $status_points;
    public $ap_spend;
    //public $ap_spend_for_remove;
    public $slot;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

