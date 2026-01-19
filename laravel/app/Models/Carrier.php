<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'default_caller_id',
        'caller_id_required',
        'sip_domain',
        'sip_port',
        'transport',
        'outbound_proxy',
        'registration_required',
        'registration_username',
        'registration_password',
    ];
}
