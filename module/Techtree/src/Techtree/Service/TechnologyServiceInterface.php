<?php
namespace Techtree\Service;

interface TechnologyServiceInterface
{
    public function getEntity($entityId);

    public function getEntities();

    public function getColonyEntity($colonyId, $entityId);

    public function getColonyEntities($colonyId = null);

    public function getEntityCosts($entityId = null);

    /**
     * @param integer $entityId
     *
     * @return boolean
     */
    public function checkRequiredBuildingsByEntityId($colonyId, $entityId);

    /**
     * @return boolean
     */
    public function checkRequiredResearchesByEntityId($colonyId, $entityId);

    /**
     * @return boolean
     */
    public function checkRequiredResourcesByEntityId($colonyId, $entityId);

    public function invest($colonyId, $entityId, $action='add', $points=1);

    /**
     * @return void
     */
    public function levelup($colonyId, $entityId);

    /**
     * @return void
     */
    public function leveldown($colonyId, $entityId);
}