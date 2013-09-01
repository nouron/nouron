<?php
namespace INNN\Entity;

use Nouron\Entity\AbstractEntity;

class Message extends AbstractEntity
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

}

