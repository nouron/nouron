<?php
namespace Trade\Service;

class Gateway extends \Nouron\Service\AbstractService
{
    public function getTechnologies($where = null)
    {
        return $this->getTable('technology_view')->fetchAll($where);
    }

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
        if (isset($this->logger)) {
            $this->logger->log(\Zend\Log\Logger::INFO, "store new Offer");
        }
        $ownerCheck = $this->getService('galaxy')->checkColonyOwner($data['colony_id'], $data['user_id']);

        if ($ownerCheck) {
            $primaryKey = array(
                'colony_id' => $data['colony_id'],
                'direction' => $data['direction'],
                'resource_id' => $data['item_id']
            );
            try {
                $table = $this->getTable('resources');
                $entity = $table->getEntity($primaryKey);
                $entity['amount'] = $data['amount'];
                $entity['price']  = $data['price'];
                $entity['restriction'] = $data['restriction'];
                $result = $table->save($entity);
            } catch ( \Exception $e) {
                $this->logger->log(\Zend\Log\Logger::ERR, $e->getMessage());
                $result = false;
            }
            return (bool) $result;
        } else {
            $this->logger->log(\Zend\Log\Logger::ERR, "add offer failed: user is not owner of selected colony");
            return false;
        }
    }

    /**
     *
     * @param  array  $data
     * @return boolean
     */
    public function addTechnologyOffer(array $data)
    {
        if (isset($this->logger)) {
            $this->logger->log(\Zend\Log\Logger::INFO, "store new Offer");
        }
        $ownerCheck = $this->getService('galaxy')->checkColonyOwner($data['colony_id'], $data['user_id']);

        if ($ownerCheck) {
            $primaryKey = array(
                'colony_id' => $data['colony_id'],
                'direction' => $data['direction'],
                'tech_id' => $data['item_id']
            );
            try {
                $table = $this->getTable('technology');
                $entity = $table->getEntity($primaryKey);
                $entity['amount'] = $data['amount'];
                $entity['price']  = $data['price'];
                $entity['restriction'] = $data['restriction'];
                $result = $table->save($entity);
            } catch ( \Exception $e) {
                $this->logger->log(\Zend\Log\Logger::ERR, $e->getMessage());
                $result = false;
            }
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
        if (isset($this->logger)) {
            $this->logger->log(\Zend\Log\Logger::INFO, "remove resource offer");
        }

        $table = $this->getTable('resources');
        $result = $table->delete($primaryKey);
        return (bool) $result;
    }

    /**
     *
     * @param array $primaryKey
     * @return boolean
     */
    public function removeTechnologyOffer(array $primaryKey)
    {
        if (isset($this->logger)) {
            $this->logger->log(\Zend\Log\Logger::INFO, "remove technology offer");
        }

        $table = $this->getTable('technology');
        $result = $table->delete($primaryKey);
        return (bool) $result;
    }
}