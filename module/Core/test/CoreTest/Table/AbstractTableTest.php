<?php
namespace CoreTest\Table;

use PHPUnit_Framework_TestCase;

abstract class AbstractTableTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $dbAdapter;

    public function initDatabase()
    {
        #print("initDatabase\n");

        $basePath = __DIR__ . '/../../../../../';
        exec("sqlite3 " . $basePath . "data/db/test.db < " . $basePath . "data/sql/truncate_all.sql");
        #exec("sqlite3 " . $basePath . "data/db/test.db < " . $basePath . "data/sql/schema.sqlite.sql");
        exec("sqlite3 " . $basePath . "data/db/test.db < " . $basePath . "data/sql/testdata.sqlite.sql");
    }

    public function initDatabaseAdapter()
    {
        if (!$this->dbAdapter) {
            #print("initDatabaseAdapter\n");
            $this->dbAdapter = new \Zend\Db\Adapter\Adapter(
                array(
                    'driver' => 'Pdo_Sqlite',
                    'database' => __DIR__ . '/../../../../../data/db/test.db'
                )
            );
        }
    }
}