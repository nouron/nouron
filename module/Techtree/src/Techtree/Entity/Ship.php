<?php
namespace Techtree\Entity;

class Ship extends AbstractTechnology
{
    public $required_research_id;
    public $required_research_level;

    /**
     * Gets the value of required_building_id.
     *
     * @return mixed
     */
    public function getRequiredResearchId()
    {
        return $this->required_research_id;
    }

    /**
     * Sets the value of required_research_id.
     *
     * @param mixed $required_research_id the required_research_id
     * @return self
     */
    public function setRequiredResearchId($required_research_id)
    {
        $this->required_research_id = $required_research_id;
        return $this;
    }

    /**
     * Gets the value of required_research_level.
     *
     * @return mixed
     */
    public function getRequiredResearchLevel()
    {
        return $this->required_research_level;
    }

    /**
     * Sets the value of required_building_level.
     *
     * @param mixed $required_research_level the required_research_level
     *
     * @return self
     */
    public function setRequiredResearchLevel($required_research_level)
    {
        $this->required_research_level = $required_research_level;
        return $this;
    }
}
