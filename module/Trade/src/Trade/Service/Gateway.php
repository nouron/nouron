<?php
namespace Trade\Service;

class Gateway extends \Nouron\Service\AbstractService
{
    /**
     * @param string|array $where
     * @return ResultSet
     */
    public function getResearches($where = null)
    {
        return $this->getTable('researches_view')->fetchAll($where);
    }

    /**
     * @param string|array $where
     * @return ResultSet
     */
    public function getResources($where = null)
    {
        return $this->getTable('resources_view')->fetchAll($where);
    }

    /**
     *
     * @param  array  $data
     * @return boolean
     */
    public function addResourceOffer(array $data)
    {
        return $this->_addOffer('resource', $data);
    }

    /**
     *
     * @param  array  $data
     * @return boolean
     */
    public function addResearchOffer(array $data)
    {
        return $this->_addOffer('research', $data);
    }

    /**
     * @param  string $type
     * @param  array  $data
     * @return boolean
     */
    private function _addOffer($type, array $data)
    {
        if (isset($this->logger)) {
            $this->logger->info("store new Offer");
        }

        if (isset($data['user_id'])) {
            // not the best solution but works for now
            $ownerCheck = $this->getService('galaxy')->checkColonyOwner($data['colony_id'], $data['user_id']);
        } else {
            $ownerCheck = false;
        }

        if ($ownerCheck) {
            $offer = array(
                'colony_id'   => $data['colony_id'],
                'direction'   => $data['direction'],
                'amount'      => $data['amount'],
                'price'       => $data['price'],
                'restriction' => $data['restriction']
            );

            if ($type == 'research') {
                $offer['research_id'] = $data['research_id'];
                $table = $this->getTable('researches');
            } elseif ($type == 'resource') {
                $offer['resource_id'] = $data['resource_id'];
                $table = $this->getTable('resources');
            } else {
                throw new Exception('invalid trade offer type');
            }

            $result = $table->save($offer);
            return (bool) $result;

        } else {
            if (isset($this->logger)) {
                $this->logger->err("add offer failed: user not given or not owner of selected colony");
            }
            return false;
        }
    }

    /**
     *
     * @param array $primaryKey
     * @return boolean
     */
    public function removeResourceOffer(array $primaryKey)
    {
        return $this->_removeOffer('resource', $primaryKey);
    }

    /**
     *
     * @param array $primaryKey
     * @return boolean
     */
    public function removeResearchOffer(array $primaryKey)
    {
        return $this->_removeOffer('research', $primaryKey);
    }

    /**
     * @param  string $type
     * @param  array $data   ATTENTION: this consists of the primary key (colony_id, item_id, direction) PLUS user_id (for owner check)
     * @return boolean
     */
    private function _removeOffer($type, array $data)
    {

        if (isset($this->logger)) {
            $this->logger->info("remove $type offer", $data);
        }

        if (isset($data['user_id'])) {
            // not the best solution but works for now
            $ownerCheck = $this->getService('galaxy')->checkColonyOwner($data['colony_id'], $data['user_id']);
        } else {
            $ownerCheck = false;
        }

        if ($ownerCheck) {
            if ($type == 'research') {
                $table = $this->getTable('researches');
            } elseif ($type == 'resource') {
                $table = $this->getTable('resources');
            } else {
                throw new Exception('invalid trade offer type');
            }

            unset($data['user_id']);
            $affectedRows = $table->deleteEntity($data);
            return (bool) $affectedRows;

        } else {
            if (isset($this->logger)) {
                $this->logger->err("remove offer failed: user not given or not owner of selected colony");
            }
            return false;
        }
    }
}