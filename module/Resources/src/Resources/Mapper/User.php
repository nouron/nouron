<?php
namespace Resources\Mapper;

use Nouron\Model\EntityInterface;

class User implements EntityInterface
{
    public $user_id;
    public $credits;
    public $supply;

    public function toArray()
    {
        return array(
            'user_id' => $this->user_id,
            'credits' => $this->credits,
            'supply' => $this->supply,
        );
    }
}

