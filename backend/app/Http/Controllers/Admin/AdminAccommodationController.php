<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accommodation;
use App\Models\AccommodationAuditLog;
use App\Models\AdminNote;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller pour la gestion des établissements par les admins
 */
class AdminAccommodationController extends Controller
{
    /**
     * Liste des établissements avec filtres
     */
    public function index(Request $request)
    {
        $query = Accommodation::with(['host', 'images', 'reviews']);

        // Filtres
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('host_id')) {
            $query->where('host_id', $request->host_id);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $accommodations = $query->paginate($perPage);

        return response()->json([
            'data' => $accommodations->items(),
            'pagination' => [
                'current_page' => $accommodations->currentPage(),
                'last_page' => $accommodations->lastPage(),
                'per_page' => $accommodations->perPage(),
                'total' => $accommodations->total(),
            ],
        ]);
    }

    /**
     * Créer un établissement (par admin)
     */
    public function store(Request $request)
    {
        $this->authorize('create', Accommodation::class);

        $validated = $request->validate([
            'host_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:hotel,lodge,guesthouse,apartment',
            'description' => 'required|string',
            'description_en' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'price_per_night' => 'required|numeric|min:0',
            'max_guests' => 'required|integer|min:1',
            'bedrooms' => 'required|integer|min:0',
            'bathrooms' => 'required|integer|min:0',
            'amenities' => 'nullable|array',
            'status' => ['nullable', Rule::in(['pending', 'published', 'rejected', 'removed', 'disabled'])],
            'opening_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'star_rating' => 'nullable|integer|min:1|max:5',
            'room_types' => 'nullable|array',
            'room_type_pricing' => 'nullable|array',
            'room_type_pricing.*.type' => 'required_with:room_type_pricing|string|max:255',
            'room_type_pricing.*.price_per_night' => 'required_with:room_type_pricing|numeric|min:0',
            'room_type_pricing.*.rooms_available' => 'nullable|integer|min:0',
            'conference_rooms_count' => 'nullable|integer|min:0',
            'conference_capacity' => 'nullable|integer|min:0',
            'restaurant_capacity' => 'nullable|integer|min:0',
            'bar_capacity' => 'nullable|integer|min:0',
            'shuttle_service' => 'nullable|boolean',
            'laundry' => 'nullable|boolean',
            'breakfast_price' => 'nullable|numeric|min:0',
            'reception_24h' => 'nullable|boolean',
            'smoking_area' => 'nullable|boolean',
            'pets_allowed' => 'nullable|boolean',
            'other_amenities' => 'nullable|string|max:1000',
            'deposit_required' => 'nullable|boolean',
            'deposit_amount' => 'nullable|string|in:first_night,percentage,fixed',
            'cancellation_policy_hours' => 'nullable|integer|min:0',
            'payment_methods' => 'nullable|array',
            'special_conditions' => 'nullable|string|max:2000',
            'breakfast_included' => 'nullable|boolean',
            'breakfast_included_persons' => 'nullable|integer|min:0|max:10',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'invoice_paid_before_hours' => 'nullable|integer|min:0',
        ]);

        // Générer un slug unique
        $baseSlug = \Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Accommodation::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $accommodation = Accommodation::create([
            'host_id' => $validated['host_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'type' => $validated['type'],
            'description' => $validated['description'],
            'description_en' => $validated['description_en'] ?? null,
            'address' => $validated['address'],
            'city' => $validated['city'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'price_per_night' => $validated['price_per_night'],
            'max_guests' => $validated['max_guests'],
            'bedrooms' => $validated['bedrooms'],
            'bathrooms' => $validated['bathrooms'],
            'amenities' => $validated['amenities'] ?? [],
            'status' => $validated['status'] ?? 'pending',
            'opening_year' => $validated['opening_year'] ?? null,
            'star_rating' => $validated['star_rating'] ?? null,
            'room_types' => $validated['room_types'] ?? [],
            'room_type_pricing' => $validated['room_type_pricing'] ?? [],
            'conference_rooms_count' => $validated['conference_rooms_count'] ?? 0,
            'conference_capacity' => $validated['conference_capacity'] ?? 0,
            'restaurant_capacity' => $validated['restaurant_capacity'] ?? 0,
            'bar_capacity' => $validated['bar_capacity'] ?? 0,
            'shuttle_service' => $request->boolean('shuttle_service', false),
            'laundry' => $request->boolean('laundry', false),
            'breakfast_price' => $validated['breakfast_price'] ?? null,
            'reception_24h' => $request->boolean('reception_24h', false),
            'smoking_area' => $request->boolean('smoking_area', false),
            'pets_allowed' => $request->boolean('pets_allowed', false),
            'other_amenities' => $validated['other_amenities'] ?? null,
            'deposit_required' => $request->boolean('deposit_required', true),
            'deposit_amount' => $validated['deposit_amount'] ?? 'first_night',
            'cancellation_policy_hours' => $validated['cancellation_policy_hours'] ?? 48,
            'payment_methods' => $validated['payment_methods'] ?? [],
            'special_conditions' => $validated['special_conditions'] ?? null,
            'breakfast_included' => $request->boolean('breakfast_included', false),
            'breakfast_included_persons' => $validated['breakfast_included_persons'] ?? 0,
            'check_in_time' => $validated['check_in_time'] ?? null,
            'check_out_time' => $validated['check_out_time'] ?? null,
            'invoice_paid_before_hours' => $validated['invoice_paid_before_hours'] ?? 48,
        ]);

        // Créer l'audit log
        AccommodationAuditLog::create([
            'accommodation_id' => $accommodation->id,
            'user_id' => $request->user()->id,
            'action' => 'created',
            'old_values' => null,
            'new_values' => $accommodation->toArray(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Établissement créé avec succès',
            'data' => $accommodation->load('host'),
        ], 201);
    }

    /**
     * Détails d'un établissement
     */
    public function show($id)
    {
        $accommodation = Accommodation::with([
            'host',
            'images',
            'reviews.user',
            'auditLogs.user',
            'adminNotes.creator',
        ])->findOrFail($id);

        $this->authorize('view', $accommodation);

        return response()->json(['data' => $accommodation]);
    }

    /**
     * Approuver un établissement
     */
    public function approve(Request $request, $id)
    {
        $accommodation = Accommodation::findOrFail($id);
        $this->authorize('approve', $accommodation);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:5000',
        ]);

        $oldStatus = $accommodation->status;
        $accommodation->update(['status' => 'published']);

        // Créer l'audit log
        AccommodationAuditLog::create([
            'accommodation_id' => $accommodation->id,
            'user_id' => $request->user()->id,
            'action' => 'approved',
            'status_before' => $oldStatus,
            'status_after' => 'published',
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Établissement approuvé avec succès',
            'data' => $accommodation->load('auditLogs'),
        ]);
    }

    /**
     * Rejeter un établissement
     */
    public function reject(Request $request, $id)
    {
        $accommodation = Accommodation::findOrFail($id);
        $this->authorize('reject', $accommodation);

        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
            'notes' => 'nullable|string|max:5000',
        ]);

        $oldStatus = $accommodation->status;
        $accommodation->update(['status' => 'rejected']);

        // Créer l'audit log
        AccommodationAuditLog::create([
            'accommodation_id' => $accommodation->id,
            'user_id' => $request->user()->id,
            'action' => 'rejected',
            'status_before' => $oldStatus,
            'status_after' => 'rejected',
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Établissement rejeté avec succès',
            'data' => $accommodation->load('auditLogs'),
        ]);
    }

    /**
     * Retirer un établissement
     */
    public function remove(Request $request, $id)
    {
        $accommodation = Accommodation::findOrFail($id);
        $this->authorize('remove', $accommodation);

        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
            'notes' => 'nullable|string|max:5000',
        ]);

        $oldStatus = $accommodation->status;
        $accommodation->update(['status' => 'removed']);

        // Créer l'audit log
        AccommodationAuditLog::create([
            'accommodation_id' => $accommodation->id,
            'user_id' => $request->user()->id,
            'action' => 'removed',
            'status_before' => $oldStatus,
            'status_after' => 'removed',
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Établissement retiré avec succès',
            'data' => $accommodation->load('auditLogs'),
        ]);
    }

    /**
     * Désactiver un établissement
     */
    public function disable(Request $request, $id)
    {
        $accommodation = Accommodation::findOrFail($id);
        $this->authorize('disable', $accommodation);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:5000',
        ]);

        $oldStatus = $accommodation->status;
        $accommodation->update(['status' => 'disabled']);

        // Créer l'audit log
        AccommodationAuditLog::create([
            'accommodation_id' => $accommodation->id,
            'user_id' => $request->user()->id,
            'action' => 'disabled',
            'status_before' => $oldStatus,
            'status_after' => 'disabled',
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Établissement désactivé avec succès',
            'data' => $accommodation->load('auditLogs'),
        ]);
    }

    /**
     * Réactiver un établissement
     */
    public function enable(Request $request, $id)
    {
        $accommodation = Accommodation::findOrFail($id);
        $this->authorize('update', $accommodation);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:5000',
        ]);

        $oldStatus = $accommodation->status;
        
        // Réactiver en remettant le statut à 'published'
        $accommodation->update(['status' => 'published']);

        // Créer l'audit log
        AccommodationAuditLog::create([
            'accommodation_id' => $accommodation->id,
            'user_id' => $request->user()->id,
            'action' => 'enabled',
            'status_before' => $oldStatus,
            'status_after' => 'published',
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Établissement réactivé avec succès',
            'data' => $accommodation->load('auditLogs'),
        ]);
    }

    /**
     * Ajouter une note interne
     */
    public function addNote(Request $request, $id)
    {
        $accommodation = Accommodation::findOrFail($id);
        $this->authorize('view', $accommodation);

        $validated = $request->validate([
            'note' => 'required|string|max:5000',
            'visibility' => ['required', 'in:admin,gerant,admin_gerant'],
            'is_important' => 'boolean',
        ]);

        $note = AdminNote::create([
            'noteable_type' => Accommodation::class,
            'noteable_id' => $accommodation->id,
            'created_by' => $request->user()->id,
            'note' => $validated['note'],
            'visibility' => $validated['visibility'],
            'is_important' => $validated['is_important'] ?? false,
        ]);

        return response()->json([
            'message' => 'Note ajoutée avec succès',
            'data' => $note->load('creator'),
        ], 201);
    }

    /**
     * Historique des modifications
     */
    public function auditLogs($id)
    {
        $accommodation = Accommodation::findOrFail($id);
        $this->authorize('view', $accommodation);

        $logs = AccommodationAuditLog::where('accommodation_id', $id)
            ->with('user')
            ->latest()
            ->paginate(50);

        return response()->json(['data' => $logs]);
    }
}

