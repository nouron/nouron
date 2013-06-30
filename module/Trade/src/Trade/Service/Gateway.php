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
        return $this->getTable('resources')->fetchAll($where);
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
        if ($data['item_type'] == 'technology') {
            $table = $this->getTable('technology');
            $column_name = 'tech_id';
        }
        else if ($data['item_type'] == 'resource') {
            $table = $this->getTable('resources');
            $column_name = 'resource_id';
        }
        else {
            return false;
        }

        $userId = 3;

        $ownerCheck = $this->getService('galaxy')->checkColonyOwner($data['colony_id'], $userId);

        if ($ownerCheck) {
            $primaryKey = array(
                'colony_id' => $data['colony_id'],
                'direction' => $data['direction'],
                $column_name => $data['item_id']
            );
            #print_r($entity);
            $entity = $table->getEntity($primaryKey);
            $entity['amount'] = $data['amount'];
            $entity['price']  = $data['price'];
            $entity['restriction'] = $data['restriction'];

            $result = $table->save($entity);
            if ((bool) $result) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function removeOffer()
    {

    }
}