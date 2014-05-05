<?php
namespace Techtree\Entity;

class ColonyBuilding extends AbstractColonyEntity
{
    private $building_id;

    /**
     * Gets the value of building_id.
     *
     * @return mixed
     */
    public function getBuildingId()
    {
        return $this->building_id;
    }

    /**
     * Sets the value of building_id.
     *
     * @param mixed $building_id the building_id
     * @return self
     */
    public function setBuildingId($building_id)
    {
        $this->building_id = abs($building_id);
        return $this;
    }
}

