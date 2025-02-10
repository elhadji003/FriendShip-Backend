<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\ShareController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Routes protégées par JWT
Route::middleware('jwt.auth')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::put('updateProfile', [AuthController::class, 'updateProfile']);
    Route::post('updateProfileImage', [AuthController::class, 'updateProfileImage']);

    // Recuperer mes users
    Route::get('all-users', [UserController::class, 'getAllUsers']);
    Route::put('/users/{id}', [UserController::class, 'updateUser']);
    Route::delete('/users/{id}', [UserController::class, 'deleteUser']);
    Route::delete('/delete-account', [UserController::class, 'deleteAccount']);

    Route::apiResource('articles', ArticleController::class);

    Route::post('/articles', [ArticleController::class, 'createArticle']);
    Route::get('/user-articles', [ArticleController::class, 'getUserArticles']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);

    Route::post('/articles/{article}/archive', [ArticleController::class, 'archive']);

    Route::post('/articles/{article}/like', [LikeController::class, 'like']);
    Route::post('/articles/{article}/dislike', [LikeController::class, 'dislike']);
    Route::get('/articles/{article}/likes', [LikeController::class, 'getLikes']);
    Route::get('/user/total-likes', [ArticleController::class, 'getUserTotalLikes']);


    Route::post('/articles/{id}/share', [ShareController::class, 'shareArticle']);
    Route::post('/articles/{article}/share', [ShareController::class, 'store']);
    Route::get('/articles/received', [ShareController::class, 'receivedArticles']);
});
