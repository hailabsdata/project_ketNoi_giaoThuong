<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_name',
        'email',
        'phone',
        'address',
        'is_active',
        'user_id', // thêm cột user_id để liên kết với User
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope active stores
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope search by name
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    /**
     * Relationship tới User
     * Lưu ý: Nếu bảng User chưa có, giá trị trả về sẽ là null
     */
    public function user()
    {
        // dùng fully qualified class name để tránh lỗi
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Relationship tới Listing
     */
    public function listings()
    {
        return $this->hasMany(Listing::class);
    }
}
