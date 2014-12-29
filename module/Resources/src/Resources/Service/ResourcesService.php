<?php
namespace Resources\Service;

class ResourcesService extends \Core\Service\AbstractService
{
    const RES_CREDITS = 1;
    const RES_SUPPLY = 2;

    /**
     * @return ResultSet
     */
    public function getResources()
    {
        return $this->getTable('resource')->fetchAll();
    }

    /**
     * Get one resource type.
     *
     * @param  integer $id
     * @return Resources_Model_Resource
     */
    public function getResource($id)
    {
        return $this->getTable('resource')->getEntity($id);
    }

//     /**
//      * Get one specific possession specified by given compound primary key.
//      * One resource posssession for a colony - not more!
//      *
//      * @param  array $keys  The compound primary key in form: array('colony_id' => 1, 'resource_id' => 2)
//      * @return ResultSet
//      */
//     public function getColonyResource(array $keys)
//     {
//         $this->_validateId($keys, array('colony_id','resource_id'));

//         $table = $this->getTable('ColonyResources');
//         foreach ($table->info('primary') as $id) {
//             $val = $keys[$id];
//             $sql[] = "$id = $val";
//         }
//         return $table->fetchAll($sql);

//     }

    /**
     *
     * @param  string    $where
     * @param  string   $order
     * @param  integer  $offset
     * @param  integer  $limit
     * @return Resultset
     */
    public function getColonyResources($where = null, $order = null, $offset = null, $limit = null)
    {
        $possessions = $this->getTable('colonyresources');
        return $possessions->fetchAll($where, $order, $offset, $limit);
    }

    /**
     *
     * @param  string    $where
     * @param  string   $order
     * @param  integer  $offset
     * @param  integer  $limit
     * @return Resultset
     */
    public function getUserResources($where = null, $order = null, $offset = null, $limit = null)
    {
        $possessions = $this->getTable('userresources');
        return $possessions->fetchAll($where, $order, $offset, $limit);
    }

    /**
     * return colony resources from given colony plus user resources from colony owner
     *
     * @param  integer $colonyId
     * @return array
     */
    public function getPossessionsByColonyId($colonyId)
    {
        $this->_validateId($colonyId);

        $galaxyGw = $this->getService('galaxy');
        $colony = $galaxyGw->getColony($colonyId);

        $possessions = $this->getColonyResources('colony_id = ' . $colonyId)->getArrayCopy('resource_id');
        $userResources = $this->getUserResources('user_id = ' . $colony->getUserId());
        foreach ($userResources as $t) {
            $add = array(
                self::RES_CREDITS => array('resource_id' => self::RES_CREDITS, 'amount'=>$t->getCredits()),
                self::RES_SUPPLY  => array('resource_id' => self::RES_SUPPLY,  'amount'=>$t->getSupply()),
            );
            $possessions += $add;
        }

        return $possessions;
    }

    /**
     * check if enough resources are on a colony
     *
     * @param  \Zend\Db\ResultSet\ResultSetInterface  $costs
     * @param  integer    $colonyId
     * @return boolean
     */
    public function check(\Zend\Db\ResultSet\ResultSetInterface $costs, $colonyId)
    {
        $this->_validateId($colonyId);
        $poss = $this->getPossessionsByColonyId($colonyId);
        // check costs
        $result = true;
        foreach ($costs as $cost) {
            $resourceId = $cost->getResourceId();
            $possession = isset($poss[$resourceId]['amount']) ? $poss[$resourceId]['amount'] : 0;
            if ($cost->getAmount() > $possession) {
                $this->getLogger()->log(
                    \Zend\Log\Logger::INFO,
                    'cost check failed: ' . $cost->getResourceId() . " " . $cost->getAmount() . ' >' . $possession);
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * Reduces the given amount.
     *
     * @param ResultSet $costs
     * @param int $colonyId
     */
    public function payCosts($costs, $colonyId)
    {
        $this->_validateId($colonyId);
        if ($costs instanceof ResulSet) {
            $costs = $costs->getArrayCopy('resource_id');
        }
        $db = $this->getTable('userresources')->getAdapter()->getDriver()->getConnection();
        try {
            $db->beginTransaction();
            foreach ($costs as $cost) {
                $result = (bool) $this->decreaseAmount($colonyId, $cost->getResourceId(), $cost->getAmount());
                if ($result == false) {
                    throw new Exception("payCosts() failed due to an error in decreaseAmount({$colonyId}, {$cost['resource_id']}, {$cost['amount']})");
                }
            }
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            $this->getLogger()->log(\Zend\Log\Logger::INFO, $e->getMessage());
            return false;
        }
    }

    /**
     * Increase resource possession
     *
     * @param int $colonyId
     * @param int $resId
     * @param int $amount
     * @param boolean $forceUserResToBeColRes
     */
    public function increaseAmount($colonyId, $resId, $amount, $forceUserResToBeColRes = false)
    {
        $this->_validateId($colonyId);
        $this->_validateId($resId);
        $amount = (int) $amount;

        if ( in_array($resId, array(self::RES_CREDITS, self::RES_SUPPLY)) AND $forceUserResToBeColRes !== true) {

            // user resources

            $colony = $this->getService('galaxy')->getColony($colonyId);
            $table = $this->getTable('userresources');
            $userId = $colony->getUserId();
            $row = $table->fetchAll("user_id = $userId")->current();

            if (empty($row)) {
                $row = array(
                    'user_id' => $userId,
                    'credits' => 0,
                    'supply' => 0
                );
            } else {
                $row = array(
                    'user_id' => $row->getUserId(),
                    'credits' => $row->getCredits(),
                    'supply'  => $row->getSupply()
                );
            }

            $this->getLogger()->log(\Zend\Log\Logger::INFO,
                "$colonyId  $resId  $amount  $forceUserResToBeColRes");

            switch ( $resId) {
                case self::RES_CREDITS :
//                     $this->getLogger()->log(\Zend\Log\Logger::INFO,
//                         "{$row['credits']} + $amount");
                    $row['credits'] = $row['credits'] + $amount;
                    break;
                case self::RES_SUPPLY :
//                     $this->getLogger()->log(\Zend\Log\Logger::INFO,
//                         "{$row['supply']} + $amount");
                    $row['supply'] = $row['supply'] + $amount;
                    break;
                default:
                    break;
            }

            return $table->save($row);

        } else {

            // colony resources

            $table = $this->getTable('colonyresources');
            $row   = $table->getEntity(array(
                        'colony_id' => $colonyId,
                        'resource_id' => $resId
                    ));

            if ( empty($row)) {
                $row = array(
                    'colony_id'   => $colonyId,
                    'resource_id' => $resId,
                    'amount'      => 0
                );
            } else {
                $row = $row->getArrayCopy();
            }

            $row['amount'] += $amount;
            return $table->save($row);
        }
    }

    /**
     * Verringere den ResourcenBesitz
     *
     * @param int $colonyId
     */
    public function decreaseAmount($colonyId, $resId, $amount)
    {
        return $this->increaseAmount($colonyId, $resId, -$amount);
    }

}