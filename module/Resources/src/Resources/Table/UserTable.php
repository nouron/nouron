<?php
namespace Resources\Table;

use Core\Table\AbstractTable;

class UserTable extends AbstractTable
{
    protected $table  = 'user_resources';
    protected $primary = 'user_id';

}

