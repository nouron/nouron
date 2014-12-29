<?php
namespace Techtree\Entity;

class Ship extends AbstractTechnology
{
    private $required_research_id;
    private $required_research_level;

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
        if (!empty($required_research_id) && (!is_numeric($required_research_id) || $required_research_id < 0)) {
            throw new \Core\Entity\Exception('invalid required research id');
        }
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
     * @return self
     */
    public function setRequiredResearchLevel($required_research_level)
    {
        if (!empty($required_research_id) && (!is_numeric($required_research_level) || $required_research_level < 0)) {
            throw new \Core\Entity\Exception('invalid required research level');
        }
        $this->required_research_level = $required_research_level;
        return $this;
    }
}
