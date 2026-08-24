<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CallLog extends Model
{
    use HasFactory;

    protected $table = 'call_logs';

    protected $fillable = [
        'user_id',
        'destination',
        'caller_id',
        'status',
        'recording_path',
        'call_uuid',
        'connected_at',
        'ended_at',
        'duration_seconds',
        'sip_status',
        'sip_reason',
        'hangup_cause',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'recording_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithRecording(Builder $query): Builder
    {
        return $query->whereNotNull('recording_path');
    }

    public function scopeForPhone(Builder $query, ?string $phone): Builder
    {
        if (blank($phone)) {
            return $query;
        }

        $normalized = preg_replace('/\D+/', '', $phone);
        if ($normalized === '') {
            return $query;
        }

        return $query->where(function (Builder $innerQuery) use ($normalized) {
            $innerQuery
                ->where('caller_id', 'like', "%{$normalized}%")
                ->orWhere('destination', 'like', "%{$normalized}%");
        });
    }

    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        return $userId ? $query->where('user_id', $userId) : $query;
    }

    public function scopeWithinPeriod(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }

    public function getRecordingUrlAttribute(): ?string
    {
        if (! $this->recording_path) {
            return null;
        }

        $disk = config('filesystems.recordings_disk', 'recordings');

        try {
            if (! Storage::disk($disk)->exists($this->recording_path)) {
                return null;
            }

            return Storage::disk($disk)->url($this->recording_path);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
