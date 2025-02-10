<?php

namespace App\Http\Controllers;

use App\Models\ProfileImage;
use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Inscription
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'gender' => 'required|string|in:male,female,other',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'gender' => $request->gender,
            'is_connected' => false,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user', 'token'), 201);
    }

    // Connexion
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        $user->update(['is_connected' => true]); // Mise à jour de is_connected à true

        // Retourner également le rôle de l'utilisateur
        return response()->json([
            'token' => $token,
            'user' => $user,
            'role' => $user->role
        ]);
    }

    // Déconnexion
    public function logout()
    {
        $user = auth()->user();
        if ($user) {
            $user->update(['is_connected' => false]); // Déconnecter l'utilisateur
        }

        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    // Récupérer les informations de l'utilisateur connecté
    public function me(Request $request)
    {
        $user = $request->user();
        $profileImage = $user->profileImage;

        if ($profileImage) {
            $user->profile_image_url = asset('storage/' . $profileImage->image_path);
        }
        return response()->json($user);
    }


    // Mettre à jour l'image de profil
    public function updateProfileImage(Request $request)
    {
        // Récupérer l'utilisateur connecté
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur non authentifié',
            ], 401);
        }

        // Valider les données reçues
        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Veuillez corriger les erreurs',
                'errors' => $validator->errors()
            ], 400);
        }

        // Vérifier s'il existe déjà une image de profil pour cet utilisateur
        $profileImage = ProfileImage::where('user_id', $user->id)->first();

        // Sauvegarder la nouvelle image
        $path = $request->file('profile_image')->store('profile_images', 'public');

        if ($profileImage) {
            // Supprimer l'ancienne image si elle existe
            if (Storage::disk('public')->exists($profileImage->image_path)) {
                Storage::disk('public')->delete($profileImage->image_path);
            }

            // Mettre à jour l'image dans la base de données
            $profileImage->image_path = $path;
            $profileImage->save();
        } else {
            // Créer une nouvelle entrée si l'utilisateur n'a pas encore d'image de profil
            $profileImage = new ProfileImage();
            $profileImage->user_id = $user->id;
            $profileImage->image_path = $path;
            $profileImage->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Image uploadée avec succès',
            'path' => url('storage/' . $path),  // Renvoie l'URL complète
            'data' => $profileImage
        ]);
    }


    // Mettre à jour le profil
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6|confirmed',
            'address' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'ville' => 'sometimes|string|max:100',
            'pays' => 'sometimes|string|max:100',
            'role' => 'sometimes|string|in:user,admin',
            'gender' => 'sometimes|string|in:male,female,other',
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

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
            'message' => 'Profil mis à jour avec succès',
            'user' => $user,
        ]);
    }
}
