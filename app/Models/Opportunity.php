<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\OpportunityStage;
use App\Enums\OpportunityStatus;

class Opportunity extends Model
{
  use HasFactory;

  protected $fillable = [
    'lead_id',
    'client_id',
    'title',
    'description',
    'stage',
    'probability',
    'expected_value',
    'expected_close_date',
    'owner_id',
    'status',
  ];

  protected $casts = [
    'stage' => OpportunityStage::class,
    'status' => OpportunityStatus::class,
    'expected_close_date' => 'date',
  ];

  public function lead()
  {
    return $this->belongsTo(Lead::class);
  }

  public function client()
  {
    return $this->belongsTo(Client::class);
  }

  public function owner()
  {
    return $this->belongsTo(User::class, 'owner_id');
  }

  public function items()
  {
    return $this->hasMany(OpportunityItem::class);
  }
}
