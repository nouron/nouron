<?php
namespace Nouron\Model;

use Zend\Db\ResultSet\Exception;
use ArrayObject;

class ResultSet extends \Zend\Db\ResultSet\ResultSet
{
    /**
     * Return the rowset as array:
     * The parameter is a column name that is used as the array index.
     * Without the parameter the function looks after a nId-column to use as array indizes.
     * If the column was not found a normal non assoziative array is returned.
     *
     * Column as Index    |
     * (standard: id):   |    No Column as Index:
     *                    |
     * array(             |    array(
     *   4 => ..,         |      0 => ...,
     *   23 => ...,       |      1 => ...,
     *   16 => ...        |      2 => ...
     * )                  |    )
     *
     *
     * ATTENTION: Think over when using the parameter! Otherwise rows can be lost!
     *
     * @param  string|null $columnAsIndex Name of the column that serves as array index.
     * @return array
     */
    public function getArrayCopy($columnAsIndex = null)
    {
        if (!empty($columnAsIndex)) {
            $result = array();
            if (is_array($columnAsIndex) && count($columnAsIndex) == 2) {
                // compound primary key
                foreach ($this as $row) {
                    $tmp = $this->convert($row);
                    if ( !isset($result[$tmp[$columnAsIndex[0]]]) ) {
                        $result[$tmp[$columnAsIndex[0]]] = array();
                    }
                    $result[ $tmp[$columnAsIndex[0]] ][ $tmp[$columnAsIndex[1]] ] = $tmp;
                }

            } elseif (is_string($columnAsIndex)) {
                // primary key is given
                foreach ($this as $row) {
                    $tmp = $this->convert($row);
                    $result[ $tmp[$columnAsIndex] ] = $tmp;
                }

            } else {
                // primary key not given
                try {
                    // try to take 'id' as primary key
                    foreach ($this as $row) {
                        $tmp = $this->convert($row);
                        $result[ $tmp['id'] ] = $tmp;
                    }
                } catch (Exception $e) {
                    // 'id' doesn't work, so just convert to array
                    //$this->log(\Zend\Log\Loger::INFO, 'getArrayCopy(): could not determine primary key');
                    $result = parent::getArrayCopy();
                }
            }

        } else {
            $result = parent::getArrayCopy();
        }

        return $result;
    }

    private function convert($row) {
        if (is_array($row)) {
            $tmp = $row;
        } elseif (method_exists($row, 'toArray')) {
            $tmp = $row->getArrayCopy();
        } elseif ($row instanceof ArrayObject) {
            $tmp = $row->getArrayCopy();
        } else {
            throw new Exception\RuntimeException(
                'Rows as part of this DataSource, with type ' . gettype($row) . ' cannot be cast to an array'
            );
        }
        return $tmp;
    }

//    public function log($type, $message)
//    {
//
//        $this->getServiceLocator()->get('logger')->log(\Zend\Log\Logger::INFO, 'message');
//    }
}