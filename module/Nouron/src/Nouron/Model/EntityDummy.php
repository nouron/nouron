<?php
namespace Techtree\Model;

use Nouron\Model;

class Technology implements EntityInterface
{
    public $id;
    public $type;
    public $purpose;
    public $name;
    public $prime_colony_only;
    public $decay;
    public $tradeable;
    public $moving_speed;

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'type' => $this->type,
            'purpose' => $this->purpose,
            'name' => $this->name,
            'prime_colony_only' => $this->prime_colony_only,
            'decay' => $this->decay,
            'tradeable' => $this->tradeable,
            'moving_speed' => $this->moving_speed
        );
    }
}

