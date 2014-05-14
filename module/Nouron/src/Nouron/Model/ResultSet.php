<?php

/**
 * @package   Nouron_Core
 * @category  Model
 */

namespace Nouron\Model;

use Zend\Db\ResultSet\Exception;

class ResultSet extends \Zend\Db\ResultSet\HydratingResultSet
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

        $hydrator = new \Zend\Stdlib\Hydrator\ClassMethods();

        if (!empty($columnAsIndex)) {

            $result = array();
            if (is_array($columnAsIndex) && count($columnAsIndex) == 2) {
                // compound primary key
                foreach ($this as $row) {
                    $tmp = is_object($row) ? $hydrator->extract($row) : $row;
                    if ( !isset($result[$tmp[$columnAsIndex[0]]]) ) {
                        $result[$tmp[$columnAsIndex[0]]] = array();
                    }
                    $result[ $tmp[$columnAsIndex[0]] ][ $tmp[$columnAsIndex[1]] ] = $tmp;
                }

            } elseif (is_string($columnAsIndex)) {
                // primary key is given
                foreach ($this as $row) {
                    $tmp = is_object($row) ? $hydrator->extract($row) : $row;
                    $result[ $tmp[$columnAsIndex] ] = $tmp;
                }

            } else {
                // primary key not given
                try {
                    // try to take 'id' as primary key
                    foreach ($this as $row) {
                        $tmp = is_object($row) ? $hydrator->extract($row) : $row;
                        $result[ $tmp['id'] ] = $tmp;
                    }
                } catch (Exception $e) {
                    // 'id' doesn't work, so just convert to array
                    //$this->log(\Zend\Log\Loger::INFO, 'getArrayCopy(): could not determine primary key');
                    $result = $this->toArray();
                }
            }
        } else {
            $result = $this->toArray();
        }

        return $result;
    }

#    private function convert($row)
#    {
#        if (is_array($row)) {
#            $tmp = $row;
#        } elseif (is_object($row)) {
#            $tmp = get_object_vars($row); #<-- TODO: this can't work anymore!!
#        } else {
#            throw new Exception\RuntimeException(
#                'Rows as part of this DataSource, with type ' . gettype($row) . ' cannot be cast to an array'
#            );
#        }
#
#        return $tmp;
#    }

}