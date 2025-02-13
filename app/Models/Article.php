<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'content', 'image', 'user_id', 'is_archived', 'likes_count', 'dislikes_count'];

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec les likes
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}
