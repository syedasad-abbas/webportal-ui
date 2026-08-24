<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InboundDid extends Model
{
    protected $fillable = [
        'carrier_id',
        'did',
        'label',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }
}
