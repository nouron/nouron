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
    protected $table   = null;
    protected $primary = 'id'; // default

    abstract public function __construct(\Zend\Db\Adapter\Adapter $adapter);

    public function fetchAll()
    {
        $resultSet = $this->select();
        return $resultSet;
    }

    public function getEntity($id)
    {
        if (is_array($this->_primary)) {
            $this->_validateId($id, $this->_primary);
        } else {
            $this->_validateId($id);
        }

        $rowset = $this->select($id);
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }

        return $row;
    }

    public function save(EntityInterface $entity)
    {
        $data = $technology->toArray();
        $id = (int) $entity->id;

        if ($id == 0) {
            $this->insert($data); // @TODO: what happens if primary is broken, so new data is inserted instead of updated
        } elseif ($this->getEntity($id)) {
            $this->update($data, array($this->_primary => $id));
        } else {
            throw new \Exception('Form id does not exist');
        }
    }

    public function deleteEntity($id)
    {
        if (is_array($id)) {
            $this->_validateId($id, $this->_primary);
            $this->delete($id);
        } else {
            $this->_validateId($id);
            $this->delete(array('id' => $id));
        }
    }
}