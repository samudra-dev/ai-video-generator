<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'video_type', 'topic', 'keywords',
        'target_audience', 'tone', 'duration', 'script', 'scenes',
        'template_used', 'status',
    ];

    protected $casts = [
        'scenes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
