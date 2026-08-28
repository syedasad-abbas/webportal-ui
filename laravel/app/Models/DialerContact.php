<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DialerContact extends Model
{
    protected $fillable = [
        'created_by',
        'name',
        'company',
        'phone',
        'phone_normalized',
        'secondary_phone',
        'email',
        'avatar_url',
        'address',
        'account_id',
        'account_status',
        'customer_since',
        'industry',
        'employees',
        'annual_revenue',
        'preferred_contact_time',
        'is_flagged',
        'labels',
    ];

    protected $casts = [
        'is_flagged' => 'boolean',
        'labels' => 'array',
        'customer_since' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DialerContactComment::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(DialerContactActivity::class)->latest();
    }
}
