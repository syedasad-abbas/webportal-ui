<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    //
    protected $fillable = [
        'list_id',
        'list_name',
        'list_description',
        'file_path',
        'import_status',
        'import_started_at',
        'import_completed_at',
        'total_rows',
        'imported_rows',
        'import_error',
    ];

}
