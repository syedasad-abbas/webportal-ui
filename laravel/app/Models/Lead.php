<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lead extends Model
{
    //
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'leads';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'status',
        'patient_name',
        'address',
        'patient_phone',
        'patient_dob',
        'sizes',
        'insurance',
        'member_id',
        'secondary_member_id',
        'products',
        'doctor_name',
        'doctor_npi',
        'medications',
        'treatments',
        'dr_last_visit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date'          => 'date',
        'patient_dob'   => 'date',
        'dr_last_visit' => 'date',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    /**
     * Optional: Accessors / Mutators if needed
     */

    // Example: Format patient phone nicely (optional)
    public function getPatientPhoneFormattedAttribute(): string
    {
        $phone = $this->patient_phone;
        if (empty($phone)) {
            return 'N/A';
        }
        // You can improve this formatting as needed
        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
    }

    /**
     * Scope for searching leads
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('patient_name', 'like', "%{$search}%")
              ->orWhere('patient_phone', 'like', "%{$search}%")
              ->orWhere('doctor_name', 'like', "%{$search}%")
              ->orWhere('insurance', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }
}
