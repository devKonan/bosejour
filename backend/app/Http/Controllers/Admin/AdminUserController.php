<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Controller pour la gestion des utilisateurs par les admins
 */
class AdminUserController extends Controller
{
    /**
     * Liste des utilisateurs avec filtres et pagination
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['roles', 'blockedByUser']);

        // Filtres
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('role_id')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->role_id);
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Détails d'un utilisateur
     */
    public function show($id)
    {
        $user = User::with([
            'roles.permissions',
            'activityLogs' => function ($query) {
                $query->latest()->limit(50);
            },
            'blockedByUser',
            'accommodations',
            'bookings',
        ])->findOrFail($id);

        $this->authorize('view', $user);

        return response()->json(['data' => $user]);
    }

    /**
     * Créer un utilisateur
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(['user', 'host', 'admin'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'blocked', 'suspended'])],
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'status' => $validated['status'] ?? 'active',
        ]);

        // Assigner les rôles RBAC si fournis
        if (!empty($validated['role_ids'])) {
            $user->roles()->sync($validated['role_ids']);
        }

        // Log de l'action
        UserActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.created',
            'model_type' => User::class,
            'model_id' => $user->id,
            'new_values' => $user->toArray(),
            'description' => "Création de l'utilisateur {$user->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['data' => $user->load('roles')], 201);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $oldValues = $user->toArray();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($id)],
            'phone' => 'nullable|string|max:20',
            'role' => ['sometimes', Rule::in(['user', 'host', 'admin'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'blocked', 'suspended'])],
        ]);

        $user->update($validated);

        // Log de l'action
        UserActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.updated',
            'model_type' => User::class,
            'model_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $user->toArray(),
            'description' => "Mise à jour de l'utilisateur {$user->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['data' => $user]);
    }

    /**
     * Bloquer un utilisateur
     */
    public function block(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('block', $user);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $user->block($request->user()->id, $validated['reason'] ?? null);

        // Log de l'action
        UserActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.blocked',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => "Blocage de l'utilisateur {$user->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Utilisateur bloqué avec succès', 'data' => $user]);
    }

    /**
     * Débloquer un utilisateur
     */
    public function unblock(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('unblock', $user);

        $user->unblock();

        // Log de l'action
        UserActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.unblocked',
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => "Déblocage de l'utilisateur {$user->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Utilisateur débloqué avec succès', 'data' => $user]);
    }

    /**
     * Assigner des rôles à un utilisateur
     */
    public function assignRoles(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('manageRoles', $user);

        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user->roles()->sync($validated['role_ids']);

        // Log de l'action
        UserActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.roles_assigned',
            'model_type' => User::class,
            'model_id' => $user->id,
            'new_values' => ['role_ids' => $validated['role_ids']],
            'description' => "Assignation de rôles à l'utilisateur {$user->name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Rôles assignés avec succès', 'data' => $user->load('roles')]);
    }

    /**
     * Historique des activités d'un utilisateur
     */
    public function activityLogs($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('view', $user);

        $logs = UserActivityLog::where('user_id', $id)
            ->latest()
            ->paginate(50);

        return response()->json(['data' => $logs]);
    }

    /**
     * Récupérer la liste des rôles disponibles
     */
    public function roles()
    {
        $this->authorize('viewAny', User::class);

        $roles = Role::where('active', true)
            ->orderBy('level')
            ->get(['id', 'name', 'display_name', 'description']);

        return response()->json(['data' => $roles]);
    }

    /**
     * Récupérer la liste des hôtes (pour la création d'établissements)
     */
    public function hosts()
    {
        $this->authorize('viewAny', User::class);

        $hosts = User::where('role', 'host')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'establishment_name']);

        return response()->json(['data' => $hosts]);
    }
}

