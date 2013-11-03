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
            $this->logger->log(\Zend\Log\Logger::INFO, "store new Offer");
        }
        $ownerCheck = $this->getService('galaxy')->checkColonyOwner($data['colony_id'], $data['user_id']);

        if ($ownerCheck) {
            $offer = array(
                'colony_id'   => $data['colony_id'],
                'direction'   => $data['direction'],
                'amount'      => $data['amount'],
                'price'       => $data['price'],
                'restriction' => $data['restriction']
            );

            if ($type == 'research') {
                $offer['research_id'] = $data['item_id'];
                $table = $this->getTable('researches');
            } elseif ($type == 'resource') {
                $offer['resource_id'] = $data['item_id'];
                $table = $this->getTable('resources');
            } else {
                throw new Exception('invalid trade offer type');
            }

            $result = $table->save($offer);
            return (bool) $result;
        } else {
            $this->logger->log(\Zend\Log\Logger::ERR, "add offer failed: user is not owner of selected colony");
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
        $this->logger->log(\Zend\Log\Logger::INFO, $primaryKey);
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
     * @param  array $primaryKey
     * @return boolean
     */
    private function _removeOffer($type, array $primaryKey)
    {
        if (isset($this->logger)) {
            $this->logger->log(\Zend\Log\Logger::INFO, "remove $type offer");
        }

        if ($type == 'research') {
            $table = $this->getTable('researches');
        } elseif ($type == 'resource') {
            $table = $this->getTable('resources');
        } else {
            throw new Exception('invalid trade offer type');
        }

        $result = $table->delete($primaryKey);
        return (bool) $result;
    }
}