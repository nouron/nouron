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

    /**
     * Gets the value of id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the value of sender.
     *
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Gets the value of attitude.
     *
     * @return mixed
     */
    public function getAttitude()
    {
        return $this->attitude;
    }

    /**
     * Gets the value of recipient.
     *
     * @return mixed
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Gets the value of tick.
     *
     * @return mixed
     */
    public function getTick()
    {
        return $this->tick;
    }

    /**
     * Gets the value of type.
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the value of subject.
     *
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Gets the value of text.
     *
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Gets the value of read.
     *
     * @return mixed
     */
    public function getRead()
    {
        return $this->read;
    }

    /**
     * Gets the value of archived.
     *
     * @return mixed
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     * Gets the value of deleted.
     *
     * @return mixed
     */
    public function getDeleted()
    {
        return $this->deleted;
    }
}

