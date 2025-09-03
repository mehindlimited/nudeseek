<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Video extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'code', // Added code field
        'description',
        'thumbnail',
        'duration',
        'published_at',
        'status',
        'access_type',
        'views',
        'likes',
        'user_id',
        'main_dir',
        'target_id'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views' => 'integer',
        'likes' => 'integer',
        'user_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($video) {
            if (empty($video->code)) {
                $video->code = self::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
            $code = '';
            for ($i = 0; $i < 16; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tags_videos', 'video_id', 'tag_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
