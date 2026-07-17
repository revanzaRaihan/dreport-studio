<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RecommendationDataset extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'category',
        'body',
        'language',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
