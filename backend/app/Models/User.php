<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role', // Rôle principal (rétrocompatibilité)
        'avatar',
        'email_verified_at',
        'date_of_birth',
        'bio',
        'address_line1',
        'address_line2',
        'city',
        'postal_code',
        'country',
        'id_type',
        'id_number',
        'id_document_path',
        'id_document_recto_path',
        'id_document_verso_path',
        'proof_of_address_path',
        'business_license_path',
        'profile_completed',
        'profile_verified',
        'profile_verified_at',
        'verification_notes',
        // Champs spécifiques aux hôtes
        'establishment_name',
        'accommodation_type',
        'phone_fixed',
        'whatsapp',
        'website',
        'facebook_page',
        'rccm',
        'cnps_number',
        'tax_account_number',
        // Nouveaux champs de gestion
        'status',
        'blocked_at',
        'blocked_by',
        'block_reason',
        'last_login_at',
        'last_login_ip',
        'login_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'blocked_at' => 'datetime',
            'last_login_at' => 'datetime',
            'login_count' => 'integer',
        ];
    }

    public function accommodations()
    {
        return $this->hasMany(Accommodation::class, 'host_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions()
    {
        return $this->hasMany(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>=', now());
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isHost(): bool
    {
        return $this->role === 'host';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Rôles RBAC (relation many-to-many)
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('assigned_by', 'assigned_at', 'expires_at')
            ->withTimestamps();
    }

    /**
     * Permissions via les rôles
     */
    public function permissions()
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $roleName): bool
    {
        // Vérifier d'abord le rôle principal (rétrocompatibilité)
        if ($this->role === $roleName) {
            return true;
        }

        // Vérifier les rôles RBAC
        return $this->roles()
            ->where('name', $roleName)
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    public function hasPermission(string $permissionName): bool
    {
        // Super admin a toutes les permissions
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('name', $permissionName)
                    ->where('active', true);
            })
            ->where('active', true)
            ->exists();
    }

    /**
     * Vérifier si l'utilisateur a au moins un des rôles
     */
    public function hasAnyRole(array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($roleName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifier si l'utilisateur est actif et non bloqué
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->blocked_at === null;
    }

    /**
     * Blocage de l'utilisateur
     */
    public function block(?int $blockedBy = null, ?string $reason = null): void
    {
        $this->update([
            'status' => 'blocked',
            'blocked_at' => now(),
            'blocked_by' => $blockedBy,
            'block_reason' => $reason,
        ]);
    }

    /**
     * Déblocage de l'utilisateur
     */
    public function unblock(): void
    {
        $this->update([
            'status' => 'active',
            'blocked_at' => null,
            'blocked_by' => null,
            'block_reason' => null,
        ]);
    }

    /**
     * Historique des activités
     */
    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class);
    }

    /**
     * Notes admin (si l'utilisateur est un hôte)
     */
    public function adminNotes()
    {
        return $this->morphMany(AdminNote::class, 'noteable');
    }

    /**
     * Historique de validation (si hôte)
     */
    public function hostValidationHistory()
    {
        return $this->hasMany(HostValidationHistory::class, 'host_id');
    }

    /**
     * Inspections effectuées (si contrôleur)
     */
    public function inspections()
    {
        return $this->hasMany(Inspection::class, 'inspector_id');
    }

    /**
     * Utilisateur qui a bloqué cet utilisateur
     */
    public function blockedByUser()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}

