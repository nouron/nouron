<?php
namespace INNN\Entity;

use Nouron\Model\EntityInterface;

class Event implements EntityInterface
{
    public $id;
    public $user_id;
    public $tick;
    public $event;
    public $area;
    public $parameters;

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tick' => $this->tick,
            'event' => $this->event,
            'area' => $this->area,
            'parameters' => $this->parameters
        );
    }
}

