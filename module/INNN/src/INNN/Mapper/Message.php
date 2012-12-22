<?php
namespace INNN\Mapper;

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

    public function toArray()
    {
        return array(
            'id' => $this->id,
            'sender' => $this->sender,
            'attitude' => $this->attitude,
            'recipient' => $this->recipient,
            'tick' => $this->tick,
            'type' => $this->type,
            'subject' => $this->subject,
            'text' => $this->text,
            'read' => $this->read,
            'archived' => $this->archived,
            'deleted' => $this->deleted
        );
    }
}

