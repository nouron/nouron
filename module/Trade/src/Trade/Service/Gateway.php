<?php
namespace Trade\Service;

class Gateway extends \Nouron\Service\Gateway
{
    public function getTechnologies($where = null)
    {
        return $this->getTable('technology')->fetchAll($where);
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
    public function addOffer(array $data)
    {
        if (isset($this->logger)) {
            $this->logger->log(\Zend\Log\Logger::INFO, "store new Offer");
        }
        if ($data['item_type'] == 'technologies') {
            $table = $this->getTable('technology');
            $column_name = 'tech_id';
        }
        else if ($data['item_type'] == 'resources') {
            $table = $this->getTable('resources');
            $column_name = 'resource_id';
        }
        else {
            $this->logger->log(\Zend\Log\Logger::ERR, "add offer failed: item_type undefined");
            return false;
        }

        $ownerCheck = $this->getService('galaxy')->checkColonyOwner($data['colony_id'], $data['user_id']);

        if ($ownerCheck) {
            $primaryKey = array(
                'colony_id' => $data['colony_id'],
                'direction' => $data['direction'],
                $column_name => $data['item_id']
            );
            try {
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

    public function removeOffer()
    {

    }
}