<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoldedProductionScrap extends Model
{
    protected $fillable = [
        'molded_production_id',
        'reason_id',
        'quantity',
    ];

    public function moldedProduction()
    {
        return $this->belongsTo(MoldedProduction::class);
    }

    public function reason()
    {
        return $this->belongsTo(Reason::class);
    }
}
