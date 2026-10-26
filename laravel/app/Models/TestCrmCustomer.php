<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestCrmCustomer extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'company', 'status', 'value', 'notes', 'intake', 'review'];
    protected $casts = ['review'=>'array', 'intake' => 'array', 'value' => 'decimal:2'];
}
