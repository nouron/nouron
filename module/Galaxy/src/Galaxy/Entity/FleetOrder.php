<?php
namespace Galaxy\Entity;

use Nouron\Entity\AbstractEntity;

class FleetOrder extends AbstractEntity
{
    public $tick;
    public $fleet_id;
    public $order;
    public $coordinates;
    public $data;
    public $was_processed;
    public $has_notified;
}

