<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::where('mail', $data['email'])->first();

        // Même message si l'email est inconnu ou si le mot de passe est faux :
        // on ne révèle jamais lequel des deux est incorrect.
        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        $token = $admin->createToken('admin-dashboard')->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'nom' => $admin->nom,
                'prenom' => $admin->prenom,
                'mail' => $admin->mail,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        // Supprime uniquement le token utilisé pour cette requête
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }
}
