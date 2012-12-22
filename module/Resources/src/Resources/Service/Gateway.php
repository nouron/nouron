<?php
namespace Resources\Service;

class Gateway
{
    private $tick;

    /**
     * Configuration settings for the Resources
     *
     * @var array
     */
    protected $config = array();

    public function __construct($tick, array $tables)
    {
        $this->tick = $tick;
        $this->tables = $tables;
    }

    private function getTable($table)
    {
        return $this->tables[strtolower($table)];
    }

    /**
     * @return ResultSet
     */
    public function getResources()
    {
        return $this->getTable('resource')->fetchAll();
    }



//     /**
//      * Get the technologies in possession from given colony.
//      *
//      * @param  integer $colonyId
//      * @return \Resources\Model\Possessions
//      * @throws \Resources\Model\Exception if invalid parameter(s)
//      */
//     public function getPossessionsByColonyId($colonyId)
//     {
//         $this->_validateId($colonyId);

//         $possessions = $this->tables['possession'];
//         return $possessions->fetchAll("colony_id = $colonyId");
//     }

//     /**
//      *
//      * @param  numeric $userId
//      * @return \Resources\Model\Possessions
//      */
//     public function getPossessionsByUserId($userId)
//     {
//         $this->_validateId($userId);

//         $galaxyGateway = new \Galaxy\Model\Gateway();
//         $colonies = $galaxyGateway->getColoniesByUserId($userId);

//         if (!$colonies->valid() || !($colonies instanceof \Galaxy\Model\Colonies)) {
//             return new \Resources\Model\Possessions(array(), $this);
//         }

//         if ( $colonies->count() > 1 ) {

//             foreach ($colonies as $col) {
//                 $coloIds[] = $col->id;
//             }

//             $coloIds = implode($coloIds, ',');

//             $possessions = $this->getPossessions("colony_id IN ($coloIds)");
//         } else {
//             $possessions = $this->getPossessionsByColonyId($colonies->id);
//         }

//         return $possessions;
//     }


    /**
     * Validates an id parameter to be a positive numeric number.
     * In case of an compound primary key the parameter $compoundKey holds the
     * indezes of the ids.
     *
     * @param  mixed       $id           the id (primary key)
     * @param  null|array  $compoundKey  OPTIONAL the indezes in case of an compoundKey
     * @throws Exception    if id is invalid
     */
    protected function _validateId($id, $compoundKey = null)
    {
        $error = false;
        if (empty($compoundKey)) {
            if (!is_numeric($id) || $id < 0) {
                throw new Exception('Parameter is not a valid id.');
            }
        } else {
            $idArray = $id;
            if (is_array($idArray)) {
                foreach ($compoundKey as $key) {
                    if (!isset($idArray[$key])) {
                        $error = true;
                        break;
                    }
                    try {
                        $this->_validateId($idArray[$key]);
                    } catch (Exception $e) {
                        $error = true;
                    }
                }
            } else {
                $error = true;
            }
            if ($error) {
                throw new Exception('Parameter is not a valid compound id.');
            }
        }
    }
}