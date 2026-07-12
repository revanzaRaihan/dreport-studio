<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PendingReport extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'student_id',
        'meeting_number',
        'report_date',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
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
