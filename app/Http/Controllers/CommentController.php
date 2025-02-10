<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    //
    public function comment(Request $request, $article_id)
    {
        // Validation des données
        $request->validate([
            "content" => "required|string|max:500",
        ]);

        // Vérifier si l'article existe
        $article = Article::findOrFail($article_id);

        // Créer le commentaire
        $comment = $article->comments()->create([
            "content" => $request->input('content'),
            "user_id" => auth()->id(),
            'article_id' => $article->id,

        ]);

        // Réponse JSON en cas de succès
        return response()->json([
            'message' => 'Commentaire ajouté avec succès.',
        ], 201);
    }


    public function getComments($article_id)
    {
        $comments = Article::findOrFail($article_id)->comments()->with('user')->get();
        return response()->json($comments, 200);
    }
}
