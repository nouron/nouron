<?php
namespace Techtree\Mapper;

use Nouron\Model\EntityInterface;

class Cost implements EntityInterface
{
    public $tech_id;
    public $resource_id;
    public $amount;

    public function toArray()
    {
        return array(
            'tech_id' => $this->tech_id,
            'resource_id' => $this->resource_id,
            'amount' => $this->amount,
        );
    }
}

