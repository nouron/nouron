<?php
namespace Techtree\Entity;

use Nouron\Entity\AbstractEntity;

class Possession extends AbstractEntity
{
    public $colony_id;
    public $tech_id;
    public $display_name;
    public $level;
    public $status_points;
    public $ap_spend;
    //public $ap_spend_for_remove;
    public $slot;
}

