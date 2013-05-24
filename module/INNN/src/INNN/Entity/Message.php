<?php
namespace INNN\Entity;

use Nouron\Model\EntityInterface;

class Message implements EntityInterface
{
    public $id;
    public $sender;
    public $attitude;
    public $recipient;
    public $tick;
    public $type;
    public $subject;
    public $text;
    public $read;
    public $archived;
    public $deleted;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}

