<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Accommodation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['accommodation.images', 'room', 'user', 'payment']);

        if ($request->user()->isUser()) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->user()->isHost()) {
            $query->whereHas('accommodation', function($q) use ($request) {
                $q->where('host_id', $request->user()->id);
            });
        }

        // Filtre par statut
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtre par statut de paiement
        if ($request->has('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        // Filtre par période (upcoming, past, all)
        if ($request->has('period') && $request->period !== 'all') {
            $now = now();
            if ($request->period === 'upcoming') {
                $query->where('check_in', '>=', $now);
            } elseif ($request->period === 'past') {
                $query->where('check_out', '<', $now);
            }
        }

        // Recherche par nom d'hébergement ou ville
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('accommodation', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $bookings = $query->paginate($perPage);

        return response()->json($bookings);
    }

    public function hostReservations(Request $request)
    {
        if (!$request->user()->isHost()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $now = \Carbon\Carbon::now();
        $hostId = $request->user()->id;

        $base = Booking::with(['accommodation.images', 'room', 'user'])
            ->whereHas('accommodation', function($q) use ($hostId) {
                $q->where('host_id', $hostId);
            })
            ->where('status', 'confirmed');

        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $endOfTwoMonths = $now->copy()->addMonthsNoOverflow(2)->endOfMonth();

        $week = (clone $base)
            ->whereBetween('check_in', [$startOfWeek, $endOfWeek])
            ->orderBy('check_in', 'asc')
            ->get();

        $month = (clone $base)
            ->whereBetween('check_in', [$startOfMonth, $endOfMonth])
            ->orderBy('check_in', 'asc')
            ->get();

        $twoMonths = (clone $base)
            ->whereBetween('check_in', [$startOfMonth, $endOfTwoMonths])
            ->orderBy('check_in', 'asc')
            ->get();

        $history = (clone $base)
            ->where('check_out', '<', $now)
            ->orderBy('check_in', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'week' => $week,
            'month' => $month,
            'two_months' => $twoMonths,
            'history' => $history,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'accommodation_id' => 'required|exists:accommodations,id',
            'room_id' => 'nullable|exists:rooms,id',
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        $accommodation = Accommodation::findOrFail($request->accommodation_id);

        // Prevent a host from booking their own accommodation
        if ($request->user()->id === $accommodation->host_id) {
            return response()->json(['message' => 'Hosts cannot book their own accommodation'], 403);
        }

        if ($accommodation->status !== 'published') {
            return response()->json(['message' => 'Accommodation not available'], 400);
        }

        $room = null;
        $pricePerNight = $accommodation->price_per_night;

        // If room_id is provided, validate room availability
        if ($request->room_id) {
            $room = \App\Models\Room::where('accommodation_id', $request->accommodation_id)
                ->where('is_active', true)
                ->findOrFail($request->room_id);

            if ($request->guests > $room->capacity) {
                return response()->json(['message' => 'Exceeds room capacity'], 400);
            }

            // Check room availability for dates
            if (!$room->isAvailableForDates($request->check_in, $request->check_out)) {
                return response()->json(['message' => 'Room not available for selected dates'], 400);
            }

            // Check for conflicting bookings on this room
            $conflictingBookings = Booking::where('room_id', $request->room_id)
                ->where('status', 'confirmed')
                ->where(function($q) use ($request) {
                    $q->whereBetween('check_in', [$request->check_in, $request->check_out])
                      ->orWhereBetween('check_out', [$request->check_in, $request->check_out])
                      ->orWhere(function($q) use ($request) {
                          $q->where('check_in', '<=', $request->check_in)
                            ->where('check_out', '>=', $request->check_out);
                      });
                })
                ->exists();

            if ($conflictingBookings) {
                return response()->json(['message' => 'Room not available for selected dates'], 400);
            }

            $pricePerNight = $room->price_per_night;
        } else {
            // Legacy: check accommodation-level availability
            if ($request->guests > $accommodation->max_guests) {
                return response()->json(['message' => 'Exceeds maximum guests'], 400);
            }

            $conflictingBookings = Booking::where('accommodation_id', $request->accommodation_id)
                ->whereNull('room_id')
                ->where('status', 'confirmed')
                ->where(function($q) use ($request) {
                    $q->whereBetween('check_in', [$request->check_in, $request->check_out])
                      ->orWhereBetween('check_out', [$request->check_in, $request->check_out])
                      ->orWhere(function($q) use ($request) {
                          $q->where('check_in', '<=', $request->check_in)
                            ->where('check_out', '>=', $request->check_out);
                      });
                })
                ->exists();

            if ($conflictingBookings) {
                return response()->json(['message' => 'Accommodation not available for selected dates'], 400);
            }

            // Capacity constraint at accommodation level: total confirmed overlapping bookings < rooms count
            $roomsCount = \App\Models\Room::where('accommodation_id', $request->accommodation_id)->where('is_active', true)->count();
            if ($roomsCount > 0) {
                $overlappingConfirmed = Booking::where('accommodation_id', $request->accommodation_id)
                    ->where('status', 'confirmed')
                    ->where(function($q) use ($request) {
                        $q->whereBetween('check_in', [$request->check_in, $request->check_out])
                          ->orWhereBetween('check_out', [$request->check_in, $request->check_out])
                          ->orWhere(function($q) use ($request) {
                              $q->where('check_in', '<=', $request->check_in)
                                ->where('check_out', '>=', $request->check_out);
                          });
                    })
                    ->count();
                if ($overlappingConfirmed >= $roomsCount) {
                    return response()->json(['message' => 'No capacity available for selected dates'], 400);
                }
            }
        }

        $nights = \Carbon\Carbon::parse($request->check_in)
            ->diffInDays(\Carbon\Carbon::parse($request->check_out));

        $totalPrice = $pricePerNight * $nights;
        $depositAmount = min($pricePerNight, $totalPrice);

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'accommodation_id' => $request->accommodation_id,
            'room_id' => $request->room_id,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'guests' => $request->guests,
            'total_price' => $totalPrice,
            'deposit_amount' => $depositAmount,
            'amount_paid' => 0,
            'status' => 'pending',
            'payment_status' => 'pending',
            'special_requests' => $request->special_requests,
            'expires_at' => now()->addHours(48),
        ]);

        // Update room availability if room booking
        if ($room) {
            $dates = $this->getDatesBetween($request->check_in, $request->check_out);
            foreach ($dates as $date) {
                \App\Models\RoomAvailability::updateOrCreate(
                    [
                        'room_id' => $room->id,
                        'date' => $date,
                    ],
                    [
                        'status' => 'occupied',
                    ]
                );
            }
        }

        return response()->json($booking->load(['accommodation', 'room']), 201);
    }

    private function getDatesBetween($start, $end)
    {
        $dates = [];
        $current = strtotime($start);
        $end = strtotime($end);

        while ($current < $end) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        return $dates;
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->user_id !== $request->user()->id && 
            $booking->accommodation->host_id !== $request->user()->id && 
            !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'status' => 'required|string|in:pending,confirmed,cancelled',
        ]);

        // Empêcher la confirmation si le paiement n'est pas effectué (sauf pour l'admin)
        if ($request->status === 'confirmed' && !$request->user()->isAdmin()) {
            // Si c'est l'hôte qui confirme, vérifier que le paiement est effectué
            if ($request->user()->isHost() && !$booking->isPaid()) {
                return response()->json([
                    'message' => 'Impossible de confirmer une réservation non payée'
                ], 400);
            }
            // Si c'est l'utilisateur qui essaie de confirmer, vérifier aussi
            if ($request->user()->isUser() && !$booking->isPaid()) {
                return response()->json([
                    'message' => 'Veuillez d\'abord effectuer le paiement'
                ], 400);
            }
        }

        $booking->update(['status' => $request->status]);

        return response()->json($booking);
    }

    public function show(Request $request, $id)
    {
        $booking = Booking::with(['accommodation.images', 'room', 'user', 'payment', 'payments'])->findOrFail($id);

        // Vérifier les permissions
        $user = $request->user();
        $canView = false;

        if ($user->isAdmin()) {
            $canView = true;
        } elseif ($user->isUser() && $booking->user_id === $user->id) {
            $canView = true;
        } elseif ($user->isHost() && $booking->accommodation->host_id === $user->id) {
            $canView = true;
        }

        if (!$canView) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($booking);
    }
}

