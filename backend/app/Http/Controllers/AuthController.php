<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $role = $request->input('role', 'user');
        
        // Validation de base
        $rules = [
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|in:user,host',
        ];

        // Validation spécifique pour les voyageurs
        if ($role === 'user') {
            $rules = array_merge($rules, [
                'name' => 'required|string|max:255',
                'nationality' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'id_type' => 'required|string|in:CNI,Passeport,Permis',
                'id_number' => 'required|string|max:255',
                'id_document_recto' => 'required|image|mimes:jpeg,jpg,png|max:5120', // 5MB max
            ]);

            // Si CNI ou Permis, le verso est requis
            if (in_array($request->input('id_type'), ['CNI', 'Permis'])) {
                $rules['id_document_verso'] = 'required|image|mimes:jpeg,jpg,png|max:5120';
            }
        } else {
            // Validation pour les hôtes
            $rules = array_merge($rules, [
                'name' => 'required|string|max:255',
                'establishment_name' => 'required|string|max:255',
                'accommodation_type' => 'required|string|in:hotel,motel,guesthouse,apartment,apartment_hotel,residence',
                'address_line1' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'whatsapp' => 'required|string|max:20',
                'phone_fixed' => 'nullable|string|max:20',
            ]);
        }

        $request->validate($rules);

        // Créer l'utilisateur
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
        ];

        // Ajouter les champs spécifiques aux voyageurs
        if ($role === 'user') {
            $userData['country'] = $request->nationality; // Utiliser country pour stocker la nationalité
            $userData['id_type'] = $request->id_type;
            $userData['id_number'] = $request->id_number;
            $userData['phone'] = $request->phone;
        } else {
            // Ajouter les champs spécifiques aux hôtes
            $userData['establishment_name'] = $request->establishment_name;
            $userData['accommodation_type'] = $request->accommodation_type;
            $userData['address_line1'] = $request->address_line1;
            $userData['city'] = $request->city;
            $userData['whatsapp'] = $request->whatsapp;
            $userData['phone_fixed'] = $request->phone_fixed;
            // Utiliser whatsapp comme numéro principal pour les hôtes
            $userData['phone'] = $request->whatsapp;
        }

        $user = User::create($userData);

        // Gérer l'upload des fichiers d'identité pour les voyageurs
        if ($role === 'user') {
            // Upload du recto
            if ($request->hasFile('id_document_recto')) {
                $path = $request->file('id_document_recto')->store('user-documents', 'public');
                $user->id_document_recto_path = $path;
            }

            // Upload du verso (si requis)
            if ($request->hasFile('id_document_verso')) {
                $path = $request->file('id_document_verso')->store('user-documents', 'public');
                $user->id_document_verso_path = $path;
            }

            $user->save();
        }

        // Charger les rôles RBAC
        $user->load('roles');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Enregistrer les informations de connexion
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'login_count' => ($user->login_count ?? 0) + 1,
        ]);

        // Charger les rôles RBAC
        $user->load('roles');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        // Charger les rôles RBAC
        $user->load('roles');
        return response()->json($user);
    }

    /**
     * Vérifier si un utilisateur a un rôle spécifique (pour NestJS)
     */
    public function checkRole($id, $role)
    {
        $user = User::findOrFail($id);
        $hasRole = $user->hasRole($role);

        return response()->json([
            'hasRole' => $hasRole,
            'userId' => $user->id,
            'role' => $role,
        ]);
    }

    /**
     * Vérifier si un utilisateur a une permission spécifique (pour NestJS)
     */
    public function checkPermission($id, $permission)
    {
        $user = User::findOrFail($id);
        $hasPermission = $user->hasPermission($permission);

        return response()->json([
            'hasPermission' => $hasPermission,
            'userId' => $user->id,
            'permission' => $permission,
        ]);
    }

    /**
     * Récupérer tous les rôles d'un utilisateur (pour NestJS)
     */
    public function getUserRoles($id)
    {
        $user = User::findOrFail($id);
        $roles = $user->roles()->get();

        return response()->json([
            'data' => $roles,
        ]);
    }

    /**
     * Récupérer toutes les permissions d'un utilisateur (pour NestJS)
     */
    public function getUserPermissions($id)
    {
        $user = User::findOrFail($id);
        $permissions = $user->permissions();

        return response()->json([
            'data' => $permissions,
        ]);
    }
}

