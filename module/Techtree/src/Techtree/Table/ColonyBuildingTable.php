<?php
namespace Techtree\Table;

use Core\Table\AbstractTable;

class ColonyBuildingTable extends AbstractTable
{
    protected $table  = 'colony_buildings';
    protected $primary = array('colony_id', 'building_id');

    #public function createEntity($array)
    #{
    #    $this->_validateId($array, $this->primary);
    #    $row = new \Techtree\Entity\ColonyBuilding();
    #    $row->exchangeArray($array);
    #    return $row;
    #}
}

