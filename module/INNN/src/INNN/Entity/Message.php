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


    /**
     * Sets the value of id.
     *
     * @param mixed $id the id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets the value of sender.
     *
     * @param mixed $sender the sender
     *
     * @return self
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Sets the value of attitude.
     *
     * @param mixed $attitude the attitude
     *
     * @return self
     */
    public function setAttitude($attitude)
    {
        $this->attitude = $attitude;

        return $this;
    }

    /**
     * Sets the value of recipient.
     *
     * @param mixed $recipient the recipient
     *
     * @return self
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * Sets the value of tick.
     *
     * @param mixed $tick the tick
     *
     * @return self
     */
    public function setTick($tick)
    {
        $this->tick = $tick;

        return $this;
    }

    /**
     * Sets the value of type.
     *
     * @param mixed $type the type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Sets the value of subject.
     *
     * @param mixed $subject the subject
     *
     * @return self
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Sets the value of text.
     *
     * @param mixed $text the text
     *
     * @return self
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Sets the value of read.
     *
     * @param mixed $read the read
     *
     * @return self
     */
    public function setRead($read)
    {
        $this->read = $read;

        return $this;
    }

    /**
     * Sets the value of archived.
     *
     * @param mixed $archived the archived
     *
     * @return self
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Sets the value of deleted.
     *
     * @param mixed $deleted the deleted
     *
     * @return self
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }
}

