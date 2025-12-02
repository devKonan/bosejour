<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\AccommodationImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AccommodationController extends Controller
{
    public function index(Request $request)
    {
        $query = Accommodation::with(['host', 'images', 'reviews'])->published();

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->has('city')) {
            $query->byCity($request->city);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('min_price') && $request->has('max_price')) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        if ($request->has('featured')) {
            $query->featured();
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 12);
        $accommodations = $query->paginate($perPage);

        return response()->json($accommodations);
    }

    public function show(Request $request, $id)
    {
        $query = Accommodation::with(['host', 'images', 'reviews.user']);

        // If authenticated and is owner or admin, allow viewing regardless of status
        $user = $request->user();
        if ($user && ($user->isAdmin() || Accommodation::where('id', $id)->where('host_id', $user->id)->exists())) {
            $accommodation = $query->findOrFail($id);
        } else {
            // Public: only published
            $accommodation = $query->published()->findOrFail($id);
        }

        return response()->json($accommodation);
    }

    public function uploadMedia(Request $request, $id)
    {
        // Utiliser le canal de log dédié pour les uploads de médias
        $mediaLog = \Log::channel('media_upload');
        
        // Log immédiatement pour s'assurer que la méthode est appelée
        $mediaLog->info("=== UPLOAD MEDIA REQUEST START ===", [
            'accommodation_id' => $id,
            'user_id' => $request->user() ? $request->user()->id : null,
            'has_file_media' => $request->hasFile('media'),
            'has_file_media_array' => $request->hasFile('media[]'),
            'content_type' => $request->header('Content-Type'),
            'all_files_keys' => array_keys($request->allFiles()),
            'request_method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        // Aussi log dans le log principal
        \Log::info("=== UPLOAD MEDIA REQUEST RECEIVED ===", [
            'accommodation_id' => $id,
            'user_id' => $request->user() ? $request->user()->id : null,
            'has_files' => $request->hasFile('media'),
            'has_files_array' => $request->hasFile('media[]'),
            'content_type' => $request->header('Content-Type'),
            'all_files_keys' => array_keys($request->allFiles()),
            'request_method' => $request->method(),
        ]);

        $mediaLog = \Log::channel('media_upload');
        
        try {
            $accommodation = Accommodation::findOrFail($id);
            $mediaLog->info("Accommodation found", ['accommodation_id' => $id, 'host_id' => $accommodation->host_id]);
        } catch (\Exception $e) {
            $mediaLog->error("Accommodation not found", ['id' => $id, 'error' => $e->getMessage()]);
            \Log::error("Accommodation not found", ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Hébergement non trouvé'], 404);
        }

        if (!$request->user()) {
            $mediaLog->warning("Unauthenticated upload attempt", ['accommodation_id' => $id]);
            \Log::warning("Unauthenticated upload attempt", ['accommodation_id' => $id]);
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        if ($accommodation->host_id !== $request->user()->id && !$request->user()->isAdmin()) {
            $mediaLog->warning("Unauthorized upload attempt", [
                'accommodation_id' => $id,
                'user_id' => $request->user()->id,
                'host_id' => $accommodation->host_id,
            ]);
            \Log::warning("Unauthorized upload attempt", [
                'accommodation_id' => $id,
                'user_id' => $request->user()->id,
                'host_id' => $accommodation->host_id,
            ]);
            return response()->json(['message' => 'Vous n\'avez pas la permission d\'uploader des images pour cet hébergement'], 403);
        }

        // Laravel traite automatiquement 'media[]' comme un tableau dans $request->file('media')
        $mediaFiles = $request->file('media', []);
        
        // Si aucun fichier n'est trouvé avec 'media', essayer 'media[]'
        if (empty($mediaFiles)) {
            $mediaFiles = $request->file('media[]', []);
        }
        
        $mediaLog = \Log::channel('media_upload');
        
        $mediaLog->info("Files received", [
            'accommodation_id' => $id,
            'files_count' => is_array($mediaFiles) ? count($mediaFiles) : ($mediaFiles ? 1 : 0),
            'is_array' => is_array($mediaFiles),
            'all_files' => array_keys($request->allFiles()),
        ]);
        
        \Log::info("Files received", [
            'accommodation_id' => $id,
            'files_count' => is_array($mediaFiles) ? count($mediaFiles) : ($mediaFiles ? 1 : 0),
            'is_array' => is_array($mediaFiles),
            'all_files' => array_keys($request->allFiles()),
        ]);
        
        // Normaliser en tableau si ce n'est pas déjà le cas
        if (!is_array($mediaFiles)) {
            $mediaFiles = $mediaFiles ? [$mediaFiles] : [];
        }
        
        // Filtrer les valeurs null
        $mediaFiles = array_filter($mediaFiles, function($file) {
            return $file !== null && $file !== false;
        });
        
        if (empty($mediaFiles)) {
            $mediaLog = \Log::channel('media_upload');
            $mediaLog->warning("No media files provided", [
                'accommodation_id' => $id,
                'request_files' => array_keys($request->allFiles()),
                'has_file_media' => $request->hasFile('media'),
                'has_file_media_array' => $request->hasFile('media[]'),
                'all_files' => $request->allFiles(),
            ]);
            \Log::warning("No media files provided", [
                'accommodation_id' => $id,
                'request_files' => array_keys($request->allFiles()),
                'has_file_media' => $request->hasFile('media'),
                'has_file_media_array' => $request->hasFile('media[]'),
                'all_files_keys' => array_keys($request->allFiles()),
            ]);
            return response()->json([
                'message' => 'Aucun fichier média fourni',
                'debug' => [
                    'has_file_media' => $request->hasFile('media'),
                    'has_file_media_array' => $request->hasFile('media[]'),
                    'all_files_keys' => array_keys($request->allFiles()),
                ]
            ], 422);
        }
        
        $mediaLog = \Log::channel('media_upload');
        $mediaLog->info("Processing media files", [
            'accommodation_id' => $id,
            'files_count' => count($mediaFiles),
        ]);
        
        \Log::info("Processing media files", [
            'accommodation_id' => $id,
            'files_count' => count($mediaFiles),
        ]);

        // Valider chaque fichier
        foreach ($mediaFiles as $index => $file) {
            if (!$file || !$file->isValid()) {
                return response()->json(['message' => "Le fichier à l'index {$index} est invalide"], 422);
            }
            
            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'video/mp4', 'video/quicktime', 'video/x-msvideo'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return response()->json(['message' => "Le type de fichier n'est pas autorisé pour le fichier à l'index {$index}"], 422);
            }
            
            if ($file->getSize() > 20480 * 1024) { // 20MB en bytes
                return response()->json(['message' => "Le fichier à l'index {$index} dépasse la taille maximale de 20MB"], 422);
            }
        }

        $existingCount = $accommodation->images()->count();
        if (($existingCount + count($mediaFiles)) > 10) {
            return response()->json(['message' => 'Maximum 10 media files allowed per accommodation'], 422);
        }

        $saved = [];
        $errors = [];
        
        foreach ($mediaFiles as $index => $file) {
            try {
                if (!$file || !$file->isValid()) {
                    $errorMsg = "Le fichier à l'index {$index} est invalide";
                    \Log::warning("Invalid file at index {$index}", [
                        'accommodation_id' => $accommodation->id,
                        'file_name' => $file ? $file->getClientOriginalName() : 'null',
                        'is_valid' => $file ? $file->isValid() : false,
                        'error' => $file ? $file->getError() : 'file is null',
                    ]);
                    $errors[] = $errorMsg;
                    continue; // Ignorer les fichiers invalides
                }
                
                $path = $file->store("accommodations/{$accommodation->id}", 'public');
                
                if (!$path) {
                    $errorMsg = "Impossible de stocker le fichier à l'index {$index}";
                    \Log::error("Failed to store file", [
                        'accommodation_id' => $accommodation->id,
                        'file_name' => $file->getClientOriginalName(),
                        'index' => $index,
                    ]);
                    $errors[] = $errorMsg;
                    continue;
                }
                
                $url = Storage::url($path);
                
                // S'assurer que l'URL est complète (avec le domaine si nécessaire)
                // Storage::url() retourne déjà une URL relative commençant par /storage/
                // On utilise asset() pour obtenir l'URL complète avec le domaine
                $fullUrl = asset($url);
                
                $mediaLog = \Log::channel('media_upload');
                $mediaLog->info("Image uploaded successfully", [
                    'accommodation_id' => $accommodation->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_mime' => $file->getMimeType(),
                    'path' => $path,
                    'url' => $url,
                    'full_url' => $fullUrl,
                ]);
                
                \Log::info("Image uploaded successfully", [
                    'accommodation_id' => $accommodation->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_mime' => $file->getMimeType(),
                    'path' => $path,
                    'url' => $url,
                    'full_url' => $fullUrl,
                ]);

                $image = AccommodationImage::create([
                    'accommodation_id' => $accommodation->id,
                    'url' => $fullUrl, // Utiliser l'URL complète
                    'is_primary' => $existingCount === 0 && $index === 0, // set first as primary if none
                    'order' => $existingCount + $index + 1,
                ]);

                $saved[] = $image;
            } catch (\Exception $e) {
                $errorMsg = "Erreur lors du traitement du fichier à l'index {$index}: " . $e->getMessage();
                \Log::error("Error storing image", [
                    'accommodation_id' => $accommodation->id,
                    'file_name' => $file ? $file->getClientOriginalName() : 'null',
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors[] = $errorMsg;
                continue;
            }
        }

        if (empty($saved) && !empty($errors)) {
            return response()->json([
                'message' => 'Aucun fichier n\'a pu être uploadé',
                'errors' => $errors
            ], 422);
        }

        if (!empty($errors)) {
            \Log::warning("Some files failed to upload", [
                'accommodation_id' => $accommodation->id,
                'saved_count' => count($saved),
                'errors' => $errors,
            ]);
        }

        return response()->json([
            'data' => $saved,
            'message' => count($saved) . ' fichier(s) uploadé(s) avec succès',
            'errors' => $errors
        ], 201);
    }

    public function store(Request $request)
    {
        // Vérifier que le profil hôte est complet et vérifié
        $user = $request->user();
        Log::info('Accommodation store request received', [
            'user_id' => $user?->id,
            'profile_completed' => $user?->profile_completed,
            'profile_verified' => $user?->profile_verified,
            'payload_preview' => $request->only([
                'name', 'type', 'city', 'price_per_night', 'max_guests'
            ]),
        ]);

        if (!$user->profile_completed) {
            Log::warning('Accommodation creation blocked: profile not completed', [
                'user_id' => $user->id,
            ]);
            return response()->json([
                'message' => 'Votre profil doit être complété à 100% avant de pouvoir ajouter un hébergement. Veuillez compléter votre profil dans la section "Profil".',
                'profile_completion_required' => true
            ], 403);
        }

        if (!$user->profile_verified) {
            Log::warning('Accommodation creation blocked: profile awaiting verification', [
                'user_id' => $user->id,
            ]);
            return response()->json([
                'message' => 'Votre profil est en attente de vérification par l\'administrateur. Vous pourrez ajouter des hébergements une fois votre profil vérifié.',
                'profile_verification_required' => true
            ], 403);
        }

        $request->validate([
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
            // Nouveaux champs
            'opening_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'star_rating' => 'nullable|integer|min:1|max:5',
            'room_types' => 'nullable|array',
            'room_type_pricing' => 'nullable|array',
            'room_type_pricing.*.type' => 'required_with:room_type_pricing|string|max:255',
            'room_type_pricing.*.price_per_night' => 'required_with:room_type_pricing|numeric|min:0',
            'room_type_pricing.*.rooms_available' => 'nullable|integer|min:0',
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

        Log::info('Accommodation request validated successfully', [
            'user_id' => $user->id,
            'amenities_count' => is_array($request->amenities) ? count($request->amenities) : 0,
            'room_types_count' => is_array($request->room_types) ? count($request->room_types) : 0,
        ]);

        // Générer un slug unique
        $baseSlug = \Str::slug($request->name);
        $slug = $baseSlug;
        $counter = 1;
        while (Accommodation::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $accommodation = Accommodation::create([
            'host_id' => $request->user()->id,
            'name' => $request->name,
            'slug' => $slug,
            'type' => $request->type,
            'description' => $request->description,
            'description_en' => $request->description_en,
            'address' => $request->address,
            'city' => $request->city,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'price_per_night' => $request->price_per_night,
            'max_guests' => $request->max_guests,
            'bedrooms' => $request->bedrooms,
            'bathrooms' => $request->bathrooms,
            'amenities' => $request->amenities ?? [],
            'status' => 'pending',
            // Nouveaux champs
            'opening_year' => $request->opening_year,
            'star_rating' => $request->star_rating,
            'room_types' => $request->room_types ?? [],
            'room_type_pricing' => $request->room_type_pricing ?? [],
            'conference_rooms_count' => $request->conference_rooms_count ?? 0,
            'conference_capacity' => $request->conference_capacity ?? 0,
            'restaurant_capacity' => $request->restaurant_capacity ?? 0,
            'bar_capacity' => $request->bar_capacity ?? 0,
            'shuttle_service' => $request->boolean('shuttle_service', false),
            'laundry' => $request->boolean('laundry', false),
            'breakfast_price' => $request->breakfast_price,
            'reception_24h' => $request->boolean('reception_24h', false),
            'smoking_area' => $request->boolean('smoking_area', false),
            'pets_allowed' => $request->boolean('pets_allowed', false),
            'other_amenities' => $request->other_amenities,
            'deposit_required' => $request->boolean('deposit_required', true),
            'deposit_amount' => $request->deposit_amount ?? 'first_night',
            'cancellation_policy_hours' => $request->cancellation_policy_hours ?? 48,
            'payment_methods' => $request->payment_methods ?? [],
            'special_conditions' => $request->special_conditions,
            'breakfast_included' => $request->boolean('breakfast_included', false),
            'breakfast_included_persons' => $request->breakfast_included_persons ?? 0,
            'check_in_time' => $request->check_in_time,
            'check_out_time' => $request->check_out_time,
            'invoice_paid_before_hours' => $request->invoice_paid_before_hours ?? 48,
        ]);

        Log::info('Accommodation created successfully', [
            'accommodation_id' => $accommodation->id,
            'user_id' => $user->id,
            'slug' => $accommodation->slug,
        ]);

        return response()->json($accommodation, 201);
    }

    public function update(Request $request, $id)
    {
        $accommodation = Accommodation::findOrFail($id);

        if ($accommodation->host_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:hotel,lodge,guesthouse,apartment',
            'description' => 'sometimes|string',
            'description_en' => 'nullable|string',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'price_per_night' => 'sometimes|numeric|min:0',
            'max_guests' => 'sometimes|integer|min:1',
            'bedrooms' => 'sometimes|integer|min:0',
            'bathrooms' => 'sometimes|integer|min:0',
            'amenities' => 'nullable|array',
            'status' => 'sometimes|string|in:pending,published,rejected,unavailable,renovation',
            // Nouveaux champs
            'opening_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'star_rating' => 'nullable|integer|min:1|max:5',
            'room_types' => 'nullable|array',
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

        // Only admin can set published/rejected
        if ($request->has('status') && in_array($request->status, ['published', 'rejected'], true) && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Only admin can set this status'], 403);
        }

        $updateData = $request->only([
            'name', 'type', 'description', 'description_en', 'address', 'city',
            'latitude', 'longitude', 'price_per_night', 'max_guests',
            'bedrooms', 'bathrooms', 'amenities', 'status',
            // Nouveaux champs
            'opening_year', 'star_rating', 'room_types', 'room_type_pricing',
            'conference_rooms_count', 'conference_capacity',
            'restaurant_capacity', 'bar_capacity',
            'shuttle_service', 'laundry', 'breakfast_price',
            'reception_24h', 'smoking_area', 'pets_allowed',
            'other_amenities', 'deposit_required', 'deposit_amount',
            'cancellation_policy_hours', 'payment_methods',
            'special_conditions', 'breakfast_included',
            'breakfast_included_persons', 'check_in_time',
            'check_out_time', 'invoice_paid_before_hours',
        ]);

        // Convertir les valeurs boolean si elles sont présentes
        if ($request->has('shuttle_service')) {
            $updateData['shuttle_service'] = $request->boolean('shuttle_service');
        }
        if ($request->has('laundry')) {
            $updateData['laundry'] = $request->boolean('laundry');
        }
        if ($request->has('reception_24h')) {
            $updateData['reception_24h'] = $request->boolean('reception_24h');
        }
        if ($request->has('smoking_area')) {
            $updateData['smoking_area'] = $request->boolean('smoking_area');
        }
        if ($request->has('pets_allowed')) {
            $updateData['pets_allowed'] = $request->boolean('pets_allowed');
        }
        if ($request->has('deposit_required')) {
            $updateData['deposit_required'] = $request->boolean('deposit_required');
        }
        if ($request->has('breakfast_included')) {
            $updateData['breakfast_included'] = $request->boolean('breakfast_included');
        }

        $accommodation->update($updateData);

        if ($request->has('name')) {
            // Générer un slug unique si le nom a changé
            $baseSlug = \Str::slug($request->name);
            $slug = $baseSlug;
            $counter = 1;
            
            // Vérifier si le slug existe déjà (sauf pour l'accommodation actuelle)
            while (Accommodation::where('slug', $slug)->where('id', '!=', $accommodation->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $accommodation->slug = $slug;
            $accommodation->save();
        }

        return response()->json($accommodation);
    }

    public function destroy(Request $request, $id)
    {
        $accommodation = Accommodation::findOrFail($id);

        if ($accommodation->host_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $accommodation->delete();

        return response()->json(['message' => 'Accommodation deleted successfully']);
    }

    public function myAccommodations(Request $request)
    {
        if (!$request->user()->isHost()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = Accommodation::where('host_id', $request->user()->id)
            ->with(['images', 'bookings' => function($q) {
                $q->where('status', 'confirmed');
            }])
            ->withCount(['bookings' => function($q) {
                $q->where('status', 'confirmed');
            }]);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $accommodations = $query->get();

        return response()->json($accommodations);
    }
}

