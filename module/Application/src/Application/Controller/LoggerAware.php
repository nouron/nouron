<?php
namespace Application\Controller;

interface LoggerAware
{
    public function getLogger();
    public function setLogger(\Laminas\Log\Logger $logger);
}