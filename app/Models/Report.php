<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Report extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'student_id',
        'student_name',
        'subject',
        'meeting_number',
        'report_date',
        'materi',
        'behavior',
        'content',
        'image_url',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the image URL, automatically resolving local storage host changes.
     */
    public function getImageUrlAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        // If it's a local storage URL, extract the path starting from /storage/
        if (preg_match('/\/storage\/reports\/.+$/i', $value, $matches)) {
            return asset($matches[0]);
        }

        return $value;
    }
}
