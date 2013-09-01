<?php
namespace Techtree\Table;

use Nouron\Table\AbstractTable;

class RequirementTable extends AbstractTable
{
    protected $table  = 'v_tech_requirements';
    protected $primary = array('tech_id', 'required_tech_id');

}

