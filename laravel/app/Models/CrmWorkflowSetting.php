<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CrmWorkflowSetting extends Model {
    protected $guarded = [];
    protected $hidden = ['api_token'];
    protected $casts = ['demo_enabled'=>'boolean', 'demo_scenario'=>'array', 'retrieval'=>'array', 'fields'=>'array', 'cards'=>'array', 'demo_report'=>'array', 'api_token'=>'encrypted'];
}
