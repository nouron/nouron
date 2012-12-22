<?php
namespace Application\Controller;

interface LoggerAware
{
    public function getLogger();
    public function setLogger(\Zend\Log\Logger $logger);
}