<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonellCost extends Model
{
    protected $table = 'personell_costs';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['personell_id', 'resource_id', 'amount'];
}
