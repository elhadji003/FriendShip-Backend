<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Récupérer tous les utilisateurs (admin seulement)
    public function getAllUsers(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'error' => 'Accès non autorisé. Seuls les administrateurs peuvent voir tous les utilisateurs.'
            ], 403);
        }

        // Récupérer tous les utilisateurs
        $users = User::all();

        return response()->json($users);
    }

    // Mettre à jour un utilisateur
    public function updateUser(Request $request, $id)
    {
        $user = auth()->user();
        $userToUpdate = User::find($id);

        // Vérifier si l'utilisateur existe
        if (!$userToUpdate) {
            return response()->json([
                'error' => 'Utilisateur non trouvé.'
            ], 404);
        }

        // Vérifier les permissions
        if ($user->role !== 'admin' && $user->id !== $userToUpdate->id) {
            return response()->json([
                'error' => 'Accès non autorisé. Vous ne pouvez pas modifier cet utilisateur.'
            ], 403);
        }

        // Valider les données de la requête
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $userToUpdate->id,
            'password' => 'sometimes|string|min:6|confirmed',
            'address' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'ville' => 'sometimes|string|max:100',
            'pays' => 'sometimes|string|max:100',
            'role' => 'sometimes|string|in:user,admin',
            'gender' => 'sometimes|string|in:male,female,other',
        ]);

        // Mettre à jour l'utilisateur
        $user->update($request->only([
            'name',
            'email',
            'address',
            'phone',
            'ville',
            'pays',
            'role',
            'gender',
        ]));

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès.',
            'user' => $userToUpdate
        ]);
    }

    // Supprimer un utilisateur (admin seulement)
    public function deleteUser($id)
    {
        $user = auth()->user();

        // Vérifier si l'utilisateur est un administrateur
        if ($user->role !== 'admin') {
            return response()->json([
                'error' => 'Accès non autorisé. Seuls les administrateurs peuvent supprimer des utilisateurs.'
            ], 403);
        }

        // Trouver l'utilisateur à supprimer
        $userToDelete = User::find($id);

        if (!$userToDelete) {
            return response()->json([
                'error' => 'Utilisateur non trouvé.'
            ], 404);
        }

        // Supprimer l'utilisateur
        $userToDelete->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès.'
        ]);
    }

    // Supprimer son propre compte
    public function deleteAccount()
    {
        $user = auth()->user();

        $user->delete();

        return response()->json([
            'message' => 'Votre compte a été supprimé avec succès.'
        ]);
    }
}
