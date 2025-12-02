<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'accommodation_id',
        'rating',
        'category_ratings',
        'comment',
        'comment_en',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'category_ratings' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class);
    }
}

