<?php
namespace Galaxy\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\AbstractTableGateway;

class ColonyTable extends AbstractTableGateway
{
    protected $table ='glx_colonies';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;

        $this->resultSetPrototype = new ResultSet();
        $this->resultSetPrototype->setArrayObjectPrototype(new Colony());

        $this->initialize();
    }

    public function fetchAll()
    {
        $resultSet = $this->select();
        return $resultSet;
    }

    public function getColony($id)
    {
        $id  = (int) $id;

        $rowset = $this->select(array('id' => $id));

        $row = $rowset->current();

        if (!$row) {
            throw new \Exception("Could not find row $id");
        }

        return $row;
    }

    public function saveColony(Colony $colony)
    {
        $data = array(
            'id' => $colony->id,
            'name' => $colony->name,
            'system_object_id' => $colony->system_object_id,
            'spot' => $colony->spot,
            'user_id' => $colony->user_id,
            'since_tick' => $colony->since_tick,
            'is_primary' => $colony->is_primary
        );

        $id = (int) $colony->id;

        if ($id == 0) {
            $this->insert($data);
        } elseif ($this->getColony($id)) {
            $this->update($data, array('id' => $id));
        } else {
            throw new \Exception('Form id does not exist');
        }
    }

    public function deleteColony($id)
    {
        $this->delete(array('id' => $id));
    }
}