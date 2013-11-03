<?php
namespace Techtree\Service;

interface TechnologyServiceInterface
{
    public function getEntity($entityId);

    public function getEntities();

    public function getColonyEntity($colonyId, $entityId);

    public function getColonyEntities($colonyId = null);

    public function getEntityCosts($entityId = null);

    public function checkRequiredBuildingsByEntityId($colonyId, $entityId);

    public function checkRequiredResearchesByEntityId($colonyId, $entityId);

    public function checkRequiredResourcesByEntityId($colonyId, $entityId);

    public function invest($colonyId, $entityId, $action='add', $points=1);

    public function levelup($colonyId, $entityId);

    public function leveldown($colonyId, $entityId);
}