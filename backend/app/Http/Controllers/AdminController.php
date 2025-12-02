<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_accommodations' => Accommodation::count(),
            'published_accommodations' => Accommodation::where('status', 'published')->count(),
            'pending_accommodations' => Accommodation::where('status', 'pending')->count(),
            'total_bookings' => Booking::count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'total_users' => User::count(),
            'total_hosts' => User::where('role', 'host')->count(),
            'total_revenue' => Booking::where('status', 'confirmed')->sum('total_price'),
            'cities' => Accommodation::select('city', DB::raw('count(*) as count'))
                ->groupBy('city')
                ->get(),
        ];

        return response()->json($stats);
    }

    public function accommodations(Request $request)
    {
        $query = Accommodation::with(['host']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $accommodations = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($accommodations);
    }

    public function approveAccommodation($id)
    {
        $accommodation = Accommodation::findOrFail($id);
        $accommodation->update(['status' => 'published']);

        return response()->json($accommodation);
    }

    public function rejectAccommodation($id)
    {
        $accommodation = Accommodation::findOrFail($id);
        $accommodation->update(['status' => 'rejected']);

        return response()->json($accommodation);
    }

    public function users()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(15);
        return response()->json($users);
    }
}

