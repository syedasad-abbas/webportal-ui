<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignLead extends Model
{
    //
    protected $fillable = ['campaign_id', 'phone', 'status', 'agent', 'reserved_at'];
}
