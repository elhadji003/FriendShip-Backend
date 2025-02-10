<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Share;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    // Partager un article avec un autre utilisateur
    public function store(Request $request, Article $article)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
        ]);

        if ($request->recipient_id == auth()->id()) {
            return response()->json(['error' => 'Vous ne pouvez pas vous envoyer un article à vous-même.'], 400);
        }

        $share = Share::create([
            'sender_id' => auth()->id(),
            'recipient_id' => $request->recipient_id,
            'article_id' => $article->id,
        ]);

        return response()->json([
            'message' => 'Article envoyé avec succès.',
            'data' => $share
        ], 201);
    }

    public function receivedArticles()
    {
        $articles = Share::where('recipient_id', auth()->id())->with('article')->get();

        return response()->json($articles);
    }
}
