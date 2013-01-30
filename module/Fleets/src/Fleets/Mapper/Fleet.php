<?php
namespace Fleets\Mapper;

use Nouron\Model\EntityInterface;

class Fleet implements EntityInterface
{
    public $id;
    public $name;
    public $user_id;
    public $coordinates;
    public $artefact;

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'coordinates' => $this->coordinates,
            'artefact' => $this->artefact
        );
    }
}

