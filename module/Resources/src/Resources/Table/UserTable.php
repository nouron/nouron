<?php
namespace Resources\Table;

use Nouron\Table\AbstractTable;

class UserTable extends AbstractTable
{
    protected $table  = 'res_user_resources';
    protected $primary = 'user_id';

}

