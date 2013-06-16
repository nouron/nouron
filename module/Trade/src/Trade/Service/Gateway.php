<?php
namespace Trade\Service;

class Gateway extends \Nouron\Service\Gateway
{
    public function getTechnologies()
    {
        return $this->getTable('technology')->fetchAll();
    }

    public function getResources()
    {
        return $this->getTable('resources')->fetchAll();
    }


    /**
     *
     * @param  array  $data
     * @return boolean
     */
    private function storeNewOffer($data)
    {
        $this->logger->log(\Zend\Log\Logger::INFO, "store new Offer");

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

        $entity = array(
            'colony_id' => $data['colony_id'],
            'direction' => $data['direction'],
            $column_name => $data['item_id']
        );

        try {
            $entity = $table->getEntity($entity);
        } catch (Exception $e) {
            null;
        }

        $entity['amount'] = $data['amount'];
        $entity['price']  = $data['price'];
        $entity['restriction'] = $data['restriction'];

        print_r($entity);

        $result = $table->save($entity);
        return (bool) $result;
    }
}