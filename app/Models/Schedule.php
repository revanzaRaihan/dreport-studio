<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Schedule extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'day_of_week',
        'start_time',
        'end_time',
        'label',
    ];

    /**
     * Students assigned to this schedule slot.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'schedule_student')
                    ->withTimestamps();
    }
}
