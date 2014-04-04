<?php
namespace Resources\Service;

class ResourcesService extends \Nouron\Service\AbstractService
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
     * @return ResultSet
     */
    public function getPossessionsByColonyId($colonyId)
    {
        $this->_validateId($colonyId);

        $galaxyGw = $this->getService('galaxy');
        $colony = $galaxyGw->getColony($colonyId);

        $possessions = $this->getColonyResources('colony_id = ' . $colonyId)->getArrayCopy('resource_id');
        $userResources = $this->getUserResources('user_id = ' . $colony->user_id);
        foreach ($userResources as $t) {
            $add = array(
                self::RES_CREDITS => array('resource_id' => self::RES_CREDITS, 'amount'=>$t->credits),
                self::RES_SUPPLY  => array('resource_id' => self::RES_SUPPLY,  'amount'=>$t->supply),
            );
            $possessions += $add;
        }

        return $possessions;
    }

    /**
     * return user resources from given user plus all resources from all his colonies
     *
     * @param  numeric $userId
     * @return ResultSet
     */
    public function getPossessionsByUserId($userId)
    {
//         $this->_validateId($userId);

//         $colonies = $this->getService('galaxy')->getColoniesByUserId($userId);

//         foreach ($colonies as $col) {
//             $coloIds[] = $col->id;
//         }
//         $coloIds = implode($coloIds, ',');
//         $colResources = $this->getColonyResources("colony_id IN ($coloIds)")->getArrayCopy('resource_id');


// //         $possessions = $this->getColonyResources('colony_id = ' . $colonyId)->getArrayCopy('resource_id');
// //         $tmp  = $this->getUserResources('user_id = ' . $colony->user_id);
// //         foreach ($tmp as $t) {
// //             $add = array(
// //                     self::RES_CREDITS => array('resource_id' => self::RES_CREDITS, 'amount'=>$t->credits),
// //                     self::RES_SUPPLY  => array('resource_id' => self::RES_SUPPLY, 'amount'=>$t->supply),
// //             );
// //             $possessions += $add;
// //         }


//         return $possessions;
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

        $result = true;

        $poss = $this->getPossessionsByColonyId($colonyId);

        // check costs
        foreach ($costs as $cost) {
            $resourceId = $cost->resource_id;
            $possession = isset($poss[$resourceId]['amount']) ? $poss[$resourceId]['amount'] : 0;
            if ($cost->amount > $possession) {

                $this->getLogger()->log(
                    \Zend\Log\Logger::INFO,
                    'cost check failed: ' . $cost->resource_id . " " . $cost->amount . ' >' . $possession);

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
        if (is_object($costs)) {
            $costs = $costs->getArrayCopy('resource_id');
        }
        foreach ($costs as $cost) {
            $this->decreaseAmount($colonyId, $cost['resource_id'], $cost['amount']);
        }
    }

    /**
     * Erhoehe den ResourcenBesitz
     *
     * @param int $colonyId
     * @param int $resId
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
            $userId = $colony->user_id;
            $row = $table->fetchAll("user_id = $userId")->current();

            if (empty($row)) {
                $row = array(
                    'user_id' => $userId,
                    'credits' => 0,
                    'supply' => 0
                );
            } else {
                $row = $row->getArrayCopy();
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