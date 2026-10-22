<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestCrmCustomer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'company', 'status', 'value', 'notes'];
    protected $casts = ['value' => 'decimal:2'];
}
