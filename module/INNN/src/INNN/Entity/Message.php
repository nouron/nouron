<?php
namespace INNN\Entity;

use Core\Entity\EntityInterface;

class Message implements EntityInterface
{
    private $id;
    #private $sender;
    private $sender_id;
    private $attitude;
    #private $recipient;
    private $recipient_id;
    private $tick;
    private $type;
    private $subject;
    private $text;
    private $is_read;
    private $is_archived;
    private $is_deleted;

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

    public function setSenderId($senderId)
    {
        $this->sender_id = $senderId;

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

    public function setRecipientId($recipientId)
    {
        $this->recipient_id = $recipientId;

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
    public function setIsRead($is_read)
    {
        $this->is_read = $is_read;

        return $this;
    }

    /**
     * Sets the value of archived.
     *
     * @param mixed $archived the archived
     *
     * @return self
     */
    public function setIsArchived($is_archived)
    {
        $this->is_archived = $is_archived;

        return $this;
    }

    /**
     * Sets the value of deleted.
     *
     * @param mixed $deleted the deleted
     *
     * @return self
     */
    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;

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

    public function getSenderId()
    {
        return $this->sender_id;
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

    public function getRecipientId()
    {
        return $this->recipient_id;
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
    public function getIsRead()
    {
        return $this->is_read;
    }

    /**
     * Gets the value of archived.
     *
     * @return mixed
     */
    public function getIsArchived()
    {
        return $this->is_archived;
    }

    /**
     * Gets the value of deleted.
     *
     * @return mixed
     */
    public function getIsDeleted()
    {
        return $this->is_deleted;
    }
}

