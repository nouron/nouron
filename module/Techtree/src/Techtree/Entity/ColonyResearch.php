<?php
namespace Techtree\Entity;

class ColonyResearch extends AbstractColonyEntity
{
    public $research_id;

    /**
     * Gets the value of research_id.
     *
     * @return mixed
     */
    public function getResearchId()
    {
        return $this->research_id;
    }

    /**
     * Sets the value of research_id.
     *
     * @param mixed $research_id the research_id
     *
     * @return self
     */
    public function setResearchId($research_id)
    {
        $this->research_id = abs($research_id);
        return $this;
    }
}

