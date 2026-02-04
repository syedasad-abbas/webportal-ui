<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignRun extends Model
{
    //
    protected $fillable = ['user_id', 'campaign_id', 'agent', 'is_running'];
}
