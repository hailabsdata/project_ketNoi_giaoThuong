<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'category',
        'price_cents',
        'currency',
        'location_text',
        'latitude',
        'longitude',
        'status',
        'is_public',
        'meta',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'meta'      => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }
}
