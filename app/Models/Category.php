<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'image', 'target_id'];

    public function target()
    {
        return $this->belongsTo(Target::class);
    }
}
