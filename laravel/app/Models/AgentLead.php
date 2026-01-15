<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgentLead extends Model
{
    use HasFactory;

    protected $fillable = [
            'user_id',
        'lead_id',
        'contract_date', // ✅ was missing in your fillable too
        'client_payment_date',
        'tpl_payment_date',
        'agent_payment_date',
        'lead_status',
    ];

        // ✅ Add this section to cast date fields
    protected $casts = [
        'contract_date' => 'date',
        'client_payment_date' => 'date',
        'tpl_payment_date' => 'date',
        'agent_payment_date' => 'date',
              ];
              
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    //
}
