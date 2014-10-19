<?php
namespace INNN\Entity;

use Nouron\Entity\EntityInterface;

class MessageView extends Message
{
    private $sender;
    private $recipient;

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
     * Gets the value of sender.
     *
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
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

}

