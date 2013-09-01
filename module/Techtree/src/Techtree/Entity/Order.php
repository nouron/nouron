<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;

class Order extends AbstractEntity
{
    public $tick;
    public $colony_id;
    public $tech_id;
    public $order;
    public $ap_ordered;
    public $is_final_step;
    public $was_progressed;
    public $has_notified;

}

