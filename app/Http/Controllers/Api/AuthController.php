<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles', 'permissions'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté.']);
    }

    public function changerMotDePasse(Request $request)
    {
        $data = $request->validate([
            'mot_de_passe_actuel' => 'required|string',
            'mot_de_passe' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($data['mot_de_passe_actuel'], $user->password)) {
            throw ValidationException::withMessages([
                'mot_de_passe_actuel' => ['Mot de passe actuel incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['mot_de_passe']),
            'must_change_password' => false,
        ]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles', 'permissions'));
    }
}