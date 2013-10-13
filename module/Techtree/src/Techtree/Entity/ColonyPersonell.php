<?php
namespace Techtree\Entity;

class ColonyPersonell extends AbstractColonyEntity
{
    public $personell_id;

    /**
     * Gets the value of personell_id.
     *
     * @return mixed
     */
    public function getPersonellId()
    {
        return $this->personell_id;
    }

    /**
     * Sets the value of personell_id.
     *
     * @param mixed $personell_id the personell_id
     * @return self
     */
    public function setPersonellId($personell_id)
    {
        $this->personell_id = abs($personell_id);

        return $this;
    }
}

