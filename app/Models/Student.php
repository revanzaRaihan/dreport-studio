<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Student extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'name',
        'subject',
        'meeting_count',
        'first_meeting_date',
    ];

    protected $casts = [
        'first_meeting_date' => 'date',
    ];

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function pendingReports()
    {
        return $this->hasMany(PendingReport::class);
    }

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_student')
                    ->withTimestamps();
    }

    protected static function booted()
    {
        static::saved(function () {
            \App\Services\Schedule\PendingReportService::clearCache();
        });

        static::deleted(function () {
            \App\Services\Schedule\PendingReportService::clearCache();
        });
    }
}
