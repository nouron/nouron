<?php

/**
 * @package   Nouron_Core
 * @category  Service
 */

namespace Core\Service;

use Laminas\Log\LoggerInterface;
use Laminas\Log\LoggerAwareInterface;

abstract class AbstractService implements LoggerAwareInterface
{
    /**
     * @var numeric
     */
    protected $tick;

    /**
     * @var array
     */
    public $tables;

    /**
     * @var array
     */
    protected $services;

    /**
     * @var \Laminas\Log\Logger
     */
    protected $logger;

    /**
     *
     * @param numeric|object $tick
     * @param array $tables
     * @param array $services OPTIONAL
     */
    public function __construct($tick, array $tables, array $services = array())
    {
        $this->setTick($tick);
        $this->setTables($tables);
        $this->setServices($services);
    }

    /*
     * @param String $name
     * @param \Core\Service\Service $Service
     */
    public function setService($name, \Core\Service\AbstractService $Service)
    {
        $this->services[strtolower($name)] = $Service;
    }

    /**
     *
     * @param array $services
     */
    public function setServices(array $services = array())
    {
        $this->services = $services;
    }

    /**
     *
     * @param String $name
     * @return \Core\Service\Service
     */
    public function getService($name)
    {
        return $this->services[ strtolower($name) ];
    }

    /**
     *
     * @return integer
     */
    public function getTick()
    {
        return (int) $this->tick;
    }

    /**
     *
     * @param numeric|\Core\Service\Tick $tick
     */
    public function setTick($tick) {
        $this->tick = (string) $tick;
    }

    /**
     *
     * @param string $table
     */
    public function getTable($table)
    {
        return $this->tables[strtolower($table)];
    }

    /**
     *
     * @param String $name
     * @param \Core\Table\AbstractTable $table
     */
    public function setTable($name, \Core\Table\AbstractTable $table)
    {
        $this->tables[strtolower($name)] = $table;
    }

    /**
     * @param array $tables
     */
    public function setTables(array $tables)
    {
        $this->tables = $tables;
    }

    /**
     *
     * @param \Laminas\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     *
     * @return \Laminas\Log\Logger
     */
    public function getLogger()
    {
        if (!($this->logger instanceof LoggerInterface)) {
            # set standard logger
            $this->logger = new \Laminas\Log\Logger();
            $this->logger->addWriter(new \Laminas\Log\Writer\Noop());
        }
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
