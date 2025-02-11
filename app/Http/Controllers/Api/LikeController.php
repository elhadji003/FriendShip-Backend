<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Like;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function like(Article $article)
    {
        $userId = auth()->id();

        // Vérifier si l'utilisateur a déjà liké ou disliké
        $like = Like::where('user_id', $userId)
            ->where('article_id', $article->id, 'user_like')
            ->first();

        if ($like) {
            if ($like->liked) {
                // Si l'utilisateur a déjà liké, on annule le like
                $like->delete();
                $article->decrement('likes_count');
                return response()->json(['message' => 'Like removed', 'user_like' => null]);
            } else {
                // Si l'utilisateur avait disliké, on annule le dislike et on ajoute un like
                $like->update(['liked' => true]);
                $article->increment('likes_count');
                $article->decrement('dislikes_count');
                return response()->json(['message' => 'Changed to like', 'user_like' => true]);
            }
        } else {
            // Si l'utilisateur n'avait rien fait, on ajoute un like
            Like::create([
                'user_id' => $userId,
                'article_id' => $article->id,
                'liked' => true,
            ]);
            $article->increment('likes_count');
            return response()->json(['message' => 'Changed to like', 'user_like' => true]);
        }
    }


    public function getLikes(Article $article)
    {
        $likesCount = $article->likes()->where('liked', true)->count();
        $dislikesCount = $article->likes()->where('liked', false)->count();

        $likers = $article->likes()
            ->where('liked', true)
            ->with('user') // Charge les relations utilisateur
            ->get()
            ->map(function ($like) {
                return [
                    'id' => $like->user->id,
                    'name' => $like->user->name,
                    'profileImage' => $like->user->profile_image_url, // Assure-toi que ce champ existe
                ];
            });

        return response()->json([
            'likes' => $likesCount,
            'dislikes' => $dislikesCount,
            'likers' => $likers,
        ]);
    }
}
