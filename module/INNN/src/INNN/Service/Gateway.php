<?php
namespace INNN\Service;

class Gateway
{
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
    public function getMessages()
    {
        return $this->getTable('message')->fetchAll();
    }

    /**
     * return ResultSet
     */
    public function getEvents()
    {
        return $this->getTable('event')->fetchAll();
    }

}