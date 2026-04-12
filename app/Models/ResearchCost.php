<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResearchCost extends Model
{
    protected $table = 'knowledge_costs';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['research_id', 'resource_id', 'amount'];
}
