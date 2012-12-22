<?php
namespace Galaxy\Model;

use Nouron\Model;

class SystemTable extends AbstractTable
{
    protected $_table   = 'glx_systems';
    protected $_primary = 'id';
    protected $_entity  = 'System';
}
