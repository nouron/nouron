<?php
namespace Resources\Service;

class Gateway extends \Nouron\Service\Gateway
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
     * @param  mixed    $where
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
     * @param  mixed    $where
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
     * Get the technologies in possession from given colony.
     *
     * @param  integer $colonyId
     * @return ResultSet
     */
    public function getPossessionsByColonyId($colonyId)
    {
        $this->_validateId($colonyId);

        $possessions = $this->tables['colonyResources'];
        return $possessions->fetchAll("colony_id = $colonyId");
    }

    /**
     *
     * @param  numeric $userId
     * @return ResultSet
     */
    public function getPossessionsByUserId($userId)
    {
        $this->_validateId($userId);

        $colonies = $this->getGateway('galaxy')->getColoniesByUserId($userId);

        foreach ($colonies as $col) {
            $coloIds[] = $col->id;
        }
        $coloIds = implode($coloIds, ',');
        $possessions = $this->getPossessions("colony_id IN ($coloIds)");

        return $possessions;
    }

    /**
     * check if enough resources are on a colony
     *
     * @param  ResultSet  $costs
     * @param  integer    $colonyId
     * @return boolean
     */
    public function check(\Zend\Db\ResultSet\ResultSetInterface $costs, $colonyId)
    {
        $this->_validateId($colonyId);

        $result = true;

        $colony = $this->getGateway('galaxy')->getColony($colonyId);

        $poss = $this->getColonyResources('colony_id = ' . $colonyId)->toArray('resource_id');
        $tmp  = $this->getUserResources('user_id = ' . $colony->user_id);
        foreach ($tmp as $t) {
            $add = array(
                self::RES_CREDITS => array('resource_id' => self::RES_CREDITS, 'amount'=>$t->credits),
                self::RES_SUPPLY  => array('resource_id' => self::RES_SUPPLY, 'amount'=>$t->supply),
            );
            $poss += $add;
        }

        // check costs
        foreach ($costs as $cost) {
            $resourceId = $cost->resource_id;
            $possession = isset($poss[$resourceId]['amount']) ? $poss[$resourceId]['amount'] : 0;
            if ($cost->amount > $possession) {

                print($cost->resource_id . " " . $cost->amount . ' >' . $possession);
                print("<br />/n");

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
        foreach ($costs as $cost) {
            $this->decreaseAmount($colonyId, $cost->resource_id, $cost->amount);
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

        if ( in_array($resId, array(self::RES_CREDITS,self::RES_SUPPLY)) AND $forceUserResToBeColRes !== true) {

            // user resources

            $colony = $this->getGateway('galaxy')->getColony($colonyId);
            $table = $this->getTable('userresources');
            $userId = $colony->user_id;
            $row = $table->fetchRow("user_id = $userId");

            if ( !empty($row) ){
                //update

                $row = $row->getArrayCopy();
                switch ( $resId) {
                    case self::RES_CREDITS :
                        $row['credits'] = $row['credits'] + $amount;
                        break;
                    case self::RES_SUPPLY :
                        $row['supply'] = $row['supply'] + $amount;
                        break;
                    default:
                        break;
                }

                $where = "user_id = " . $userId;
                return $table->update($row, $where);
            }
            else {
                //insert
                $row = array(
                    'user_id'    => $userId,
                    'credits' => 0,
                    'supply'  => 0
                );

                switch ( $resId) {
                    case self::RES_CREDITS :
                        $row['credits'] = $amount;
                        break;
                    case self::RES_SUPPLY :
                        $row['supply'] = $amount;
                        break;
                    default:
                        break;
                }
                return $table->insert($row);
            }

        } else {

            // colony resources

            $table = $this->getTable('colonyresources');
            $row = $table->fetchRow("colony_id = $colonyId AND resource_id = $resId");

            if ( !empty($row) ){
                //update
                $row = $row->getArrayCopy();
                $data = $row['amount'] + $amount;
                $data  = array('amount' => $data);
                $where = array('colony_id = ' .$colonyId, 'resource_id = ' . $resId);
                return $table->update($data, $where);
            }
            else {
                //insert
                $data = array(
                    'colony'   => $colonyId,
                    'resource' => $resId,
                    'amount'    => $amount
                );
                return $table->insert($data);
            }
        }
    }

    /**
     * Verringere den ResourcenBesitz
     *
     * @param int $colonyId
     * @param int $techId
     */
    public function decreaseAmount($colonyId, $resId, $amount)
    {
        return $this->increaseAmount($colonyId, $resId, -$amount);
    }

}