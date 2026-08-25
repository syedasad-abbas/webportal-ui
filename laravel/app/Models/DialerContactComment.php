<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DialerContactComment extends Model
{
    protected $fillable = [
        'dialer_contact_id',
        'user_id',
        'body',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(DialerContact::class, 'dialer_contact_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
