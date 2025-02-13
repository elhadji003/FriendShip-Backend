<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    // Récupérer tous les articles
    public function index(Request $request)
    {
        // Récupérer l'utilisateur authentifié (si connecté)
        $user = auth()->user();

        $articles = Article::with(['user', 'likes',])
            ->orderBy('created_at', 'desc')
            ->paginate(4);

        // Ajouter la logique user_like à chaque article
        $articles->getCollection()->transform(function ($article) use ($user) {
            $article->user_like = $user ? $article->likes->contains('user_id', $user->id) : false;
            return $article;
        });

        return response()->json([
            'articles' => $articles->items(),
            'current_page' => $articles->currentPage(),
            'last_page' => $articles->lastPage(),
            'per_page' => $articles->perPage(),
            'total' => $articles->total(),
        ], 200);
    }



    public function getUserArticles()
    {
        $user = auth()->user();

        $articles = Article::where('user_id', $user->id)
            ->with(['user:id,name', 'likes.user:id,name,profile_image_url']) // Charger les utilisateurs qui ont liké
            ->orderBy('created_at', 'desc')
            ->paginate(4);

        $articles->transform(function ($article) {
            $article->image = $article->image ? url('storage/' . $article->image) : null;

            // Ajouter les informations des utilisateurs qui ont liké
            $article->likers = $article->likes->map(function ($like) {
                $liker = $like->user;
                return [
                    'id' => $liker->id,
                    'name' => $liker->name,
                    'avatar' => $liker->profile_image_url,  // Utilisation du getter pour obtenir l'URL de l'image
                ];
            });

            return $article;
        });

        return response()->json([
            'articles' => $articles->items(),
            'current_page' => $articles->currentPage(),
            'last_page' => $articles->lastPage(),
            'per_page' => $articles->perPage(),
        ], 200);
    }

    // Créer un article
    public function createArticle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'Seuls les formats jpeg, png, jpg et gif sont autorisés.',
            'image.max' => 'La taille de l\'image ne doit pas dépasser 2 Mo.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('articles', 'public');
        }

        $article = Article::create([
            'title' => $request->title,
            'content' => $request->content,
            'image' => $imagePath,
            'user_id' => Auth::id(),
        ]);

        return response()->json($article, 201);
    }


    // Récupérer un article par ID
    public function show(Article $article)
    {
        return response()->json($article->load(['user', 'likes',]));
    }

    // Modifier un article
    public function update(Request $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article non trouvé'], 404);
        }

        if ($article->user_id !== Auth::id()) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier cet article'], 403);
        }

        // Validation conditionnelle: Le titre est requis uniquement s'il est modifié
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255', // Le titre est nullable
            'content' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Mise à jour de l'image si nécessaire
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($article->image) {
                Storage::disk('public')->delete($article->image);
            }

            // Sauvegarder la nouvelle image
            $article->image = $request->file('image')->store('articles', 'public');
        }

        $article->update($request->only([
            'title',
            'content',
        ]));
        return response()->json($article);
    }


    // Supprimer un article
    public function destroy($id)
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json(['message' => 'Article non trouvé'], 404);
        }

        if ($article->user_id !== Auth::id()) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer cet article'], 403);
        }

        // Supprimer l'image si elle existe
        if ($article->image) {
            Storage::disk('public')->delete($article->image);
        }

        $article->delete();

        return response()->json(['message' => 'Article supprimé avec succès']);
    }

    // Archiver un article
    public function archive(Article $article)
    {
        $article->update(['is_archived' => true]);
        return response()->json($article);
    }

    public function getUserTotalLikes()
    {
        $user = auth()->user();

        $articles = Article::where('user_id', $user->id)->get();

        $totalLikes = 0;
        foreach ($articles as $article) {
            $totalLikes += $article->likes()->where('liked', true)->count();
        }
        return response()->json(['total_likes' => $totalLikes]);
    }
}
