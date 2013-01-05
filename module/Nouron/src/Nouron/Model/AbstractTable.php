<?php
namespace Nouron\Model;

use Zend\Db\TableGateway\AbstractTableGateway,
    Zend\Db\Adapter\Adapter,
    Zend\Db\ResultSet\ResultSet,
    Nouron\Model\EntityInterface;

/**
 * This is the abstract class for all table classes. It implements all standard
 * methods for table classes so they are 'ready-to-use' for new table classes.
 *
 */
abstract class AbstractTable extends AbstractTableGateway
{
    /**
     * @var string
     */
    protected $table   = null;

    /**
     * @var string
     */
    protected $primary = 'id'; // default

    /**
     *
     * @param \Zend\Db\Adapter\Adapter $adapter
     */
    abstract public function __construct(\Zend\Db\Adapter\Adapter $adapter);

    /**
     *
     * @param string|array $where
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    public function fetchAll($where = null)
    {
        $resultSet = $this->select($where);
        return $resultSet;
    }

    /**
     *
     * @param string|array $where
     * @return Ambigous <multitype:, ArrayObject, NULL, \ArrayObject, unknown>
     */
    public function fetchRow($where)
    {
        return $this->fetchAll($where)->current();
    }

    /**
     *
     * @param numeric $id
     * @throws \Exception
     * @return Ambigous <multitype:, ArrayObject, NULL, \ArrayObject, unknown>
     */
    public function getEntity($id)
    {
        if (is_array($this->primary)) {
            $this->_validateId($id, $this->primary);
        } else {
            $this->_validateId($id);
        }

        $rowset = $this->select("id = $id");
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }

        return $row;
    }

//     /**
//      *
//      * @param EntityInterface $entity
//      * @throws \Exception
//      */
//     public function save(EntityInterface $entity)
//     {
//         $data = $entity->toArray();
//         $id = (int) $entity->id;

//         if ($id == 0) {
//             $this->insert($data); // @TODO: what happens if primary is broken, so new data is inserted instead of updated
//         } elseif ($this->getEntity($id)) {
//             $this->update($data, array($this->primary => $id));
//         } else {
//             throw new \Exception('Form id does not exist');
//         }
//     }

    /**
     * save the object to db:
     * - insert if new data
     * - else update
     *
     * TODO find a general solution for return type
     *       (now: update returns number of lines, insert return pk)
     *
     * @return integer|array The primary key
     */
    public function save($entity)
    {
        // make a copy of row data (to avoid changing original data):
        if ($entity instanceof EntityInterface) {
            $data = $entity->toArray();
        } elseif (is_array($entity)) {
            $data = $entity;
        } else {
            throw new Exception('Invalid parameter type for save().');
        }

        $primary = (array) $this->primary;
        // primary is now an array so we can handle scalar and compound keys the same way:

        $where = array();

        \Zend\Debug\Debug::dump($primary);
        \Zend\Debug\Debug::dump($data);

        foreach ($primary as $key) {
            $val = $data[$key];
            if ( is_numeric($val) || !empty($val) ) {
                $where[] = "$key = $val";
            } else {
                $missingPrimaryKey = true;
            }
        }

        // update if data set is in table,
        // else insert the new data to the table
        $result = $this->fetchAll($where)->toArray();
        if (!empty( $result ) && !isset($missingPrimaryKey)) {
            // if check is not empty the record set exists and has to be updated
            $result = $this->update($data, $where);
        } else {
            $result = $this->insert($data);
        }

//         $cache = Zend_Registry::get('cache');
//         $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('user'));
        return $result;
    }

    /**
     *
     * @param numeric $id
     */
    public function deleteEntity($id)
    {
        if (is_array($id)) {
            $this->_validateId($id, $this->primary);
            $this->delete($id);
        } else {
            $this->_validateId($id);
            $this->delete(array('id' => $id));
        }
    }

    /**
     * Validates an id parameter to be a positive numeric number.
     * In case of an compound primary key the parameter $compoundKey holds the
     * indezes of the ids.
     *
     * @param  mixed       $id           the id (primary key)
     * @param  null|array  $compoundKey  OPTIONAL the indezes in case of an compoundKey
     * @throws Nouron\Model\Exception    if id is invalid
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