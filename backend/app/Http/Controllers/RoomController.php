<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Accommodation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function index(Request $request, $accommodationId)
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        
        $query = Room::where('accommodation_id', $accommodationId)
            ->with(['availabilities']);

        // Only show active rooms to public
        if (!$request->user() || !$request->user()->isHost() || $accommodation->host_id !== $request->user()?->id) {
            $query->active();
        }

        $rooms = $query->get();

        return response()->json($rooms);
    }

    public function show($accommodationId, $id)
    {
        $room = Room::with(['accommodation', 'availabilities'])
            ->where('accommodation_id', $accommodationId)
            ->findOrFail($id);

        return response()->json($room);
    }

    public function store(Request $request, $accommodationId)
    {
        $accommodation = Accommodation::findOrFail($accommodationId);

        if ($accommodation->host_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
            'amenities' => 'nullable|array',
            'bedrooms' => 'required|integer|min:1',
            'bathrooms' => 'required|integer|min:1',
        ]);

        $room = Room::create([
            'accommodation_id' => $accommodationId,
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'description_en' => $request->description_en,
            'capacity' => $request->capacity,
            'price_per_night' => $request->price_per_night,
            'amenities' => $request->amenities ?? [],
            'bedrooms' => $request->bedrooms,
            'bathrooms' => $request->bathrooms,
            'is_active' => true,
        ]);

        return response()->json($room->load('availabilities'), 201);
    }

    public function update(Request $request, $accommodationId, $id)
    {
        $room = Room::where('accommodation_id', $accommodationId)->findOrFail($id);
        $accommodation = $room->accommodation;

        if ($accommodation->host_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'capacity' => 'sometimes|integer|min:1',
            'price_per_night' => 'sometimes|numeric|min:0',
            'amenities' => 'nullable|array',
            'bedrooms' => 'sometimes|integer|min:1',
            'bathrooms' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        $room->update($request->only([
            'name', 'type', 'description', 'description_en', 'capacity',
            'price_per_night', 'amenities', 'bedrooms', 'bathrooms', 'is_active'
        ]));

        return response()->json($room->load('availabilities'));
    }

    public function destroy(Request $request, $accommodationId, $id)
    {
        $room = Room::where('accommodation_id', $accommodationId)->findOrFail($id);
        $accommodation = $room->accommodation;

        if ($accommodation->host_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Check if room has active bookings
        $hasBookings = $room->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($hasBookings) {
            return response()->json([
                'message' => 'Cannot delete room with active bookings'
            ], 400);
        }

        $room->delete();

        return response()->json(['message' => 'Room deleted successfully']);
    }
}
