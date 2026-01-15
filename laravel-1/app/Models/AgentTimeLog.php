<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class AgentTimeLog extends Model
{   
    use HasFactory;

    protected $fillable = [
        'user_id',
        'log_date',
        'login_minutes',
        'pause_minutes',
        'invoice_minutes',
    ];

    public function user()
    {
              return $this->belongsTo(User::class, 'user_id'); // ✅ fixed
    }
     // 👇 Accessor + Mutator using Laravel 9+ Attribute casting
    protected function logDate(): Attribute
    {
        return Attribute::make(
            // Format when reading
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d'),

            // Normalize when setting
            set: fn ($value) => Carbon::parse($value)->format('Y-m-d')
        );
    }
    //
}
