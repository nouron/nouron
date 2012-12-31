<?php
namespace Nouron\Service;

use Zend\Log\LoggerInterface;
use Zend\Log\LoggerAwareInterface;

abstract class Gateway implements LoggerAwareInterface
{
    /**
     * @var numeric
     */
    protected $tick;

    /**
     * @var array
     */
    protected $tables;

    /**
     * @var array
     */
    protected $gateway;

    /**
     * @var \Zend\Log\Logger
     */
    protected $logger;

    /**
     *
     * @param numeric $tick
     * @param array $tables
     * @param array $gateways OPTIONAL
     * @param \Resources\Service\Gateway $resourcesGateway
     */
    public function __construct($tick, array $tables, array $gateways = array())
    {
        $this->setTick($tick);
        $this->setTables($tables);
        $this->setGateways($gateways);
    }

    /**
     *
     * @param array $gateways
     */
    public function setGateways(array $gateways = array())
    {
        $this->gateways = $gateways;
    }

    /**
     *
     * @param String $name
     * @return \Nouron\Service\Gateway
     */
    public function getGateway($name)
    {
        return $this->gateways[ $name ];
    }

    /**
     *
     * @return numeric
     */
    public function getTick()
    {
        return $this->tick;
    }

    /**
     *
     * @param numeric $tick
     */
    public function setTick($tick) {
        $this->tick = (string) $tick;
    }

    /**
     *
     * @param string $table
     */
    protected function getTable($table)
    {
        return $this->tables[strtolower($table)];
    }

    /**
     * @param array $tables
     */
    protected function setTables(array $tables)
    {
        $this->tables = $tables;
    }

    /**
     *
     * @param \Zend\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     *
     * @return \Zend\Log\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Validates an id parameter to be a positive numeric number.
     * In case of an compound primary key the parameter $compoundKey holds the
     * indezes of the ids.
     *
     * @param  mixed       $id           the id (primary key)
     * @param  null|array  $compoundKey  OPTIONAL the indezes in case of an compoundKey
     * @throws Exception   if id is invalid
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