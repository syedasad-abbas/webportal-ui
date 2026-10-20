<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VicidialCredential extends Model
{
    protected $fillable = ['user_id', 'agent_user', 'sip_username', 'sip_password', 'api_username', 'api_password', 'crm_agent_id', 'crm_team_id'];
    protected $hidden = ['sip_password', 'api_username', 'api_password'];
    protected $casts = ['api_password' => 'encrypted'];
}
