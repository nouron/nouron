<?php
namespace INNN\Table;

use Core\Table\AbstractTable;

class MessageTable extends AbstractTable
{
    protected $table  = 'innn_messages';
    protected $primary = 'id';
}

