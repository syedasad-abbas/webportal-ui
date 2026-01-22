<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SipCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sip_username',
        'sip_password',
    ];

    protected $hidden = [
        'sip_password',
    ];
}
