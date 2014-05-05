<?php
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

        if (!empty($columnAsIndex)) {

            $result = array();
            if (is_array($columnAsIndex) && count($columnAsIndex) == 2) {

                $attr1 = str_replace(' ', '', ucwords(str_replace('_', ' ', $columnAsIndex[0])));
                $attr2 = str_replace(' ', '', ucwords(str_replace('_', ' ', $columnAsIndex[1])));

                // compound primary key
                foreach ($this as $row) {

                    $getattr1 = 'get' . $attr1;
                    $getattr2 = 'get' . $attr2;

                    if ( !isset($result[$row->$getattr1()]) ) {
                        $result[$row->$getattr1()] = array();
                    }
                    $result[ $row->$getattr1() ][ $row->$getattr2() ] = $tmp;
                }

            } elseif (is_string($columnAsIndex)) {
                // primary key is given
                $attr = str_replace(' ', '', ucwords(str_replace('_', ' ', $columnAsIndex)));
                foreach ($this as $row) {
                    $func = 'get' . $attr;
                    $result[ $row->$func() ] = $row;
                }

            } else {
                // primary key not given
                try {
                    // try to take 'id' as primary key
                    foreach ($this as $row) {
                        $result[ $tmp->getId() ] = $row;
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