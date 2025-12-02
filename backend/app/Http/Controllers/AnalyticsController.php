<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Accommodation;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function hostDashboard(Request $request)
    {
        if (!$request->user()->isHost()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $hostId = $request->user()->id;

        // Total bookings
        $totalBookings = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })->count();

        // Confirmed bookings
        $confirmedBookings = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })->where('status', 'confirmed')->count();

        // Total revenue
        $totalRevenue = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })->where('status', 'confirmed')->sum('total_price');

        // Monthly revenue (last 6 months)
        $monthlyRevenue = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })
        ->where('status', 'confirmed')
        ->where('created_at', '>=', Carbon::now()->subMonths(6))
        ->select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('SUM(total_price) as revenue')
        )
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // Occupancy rate (last 30 days)
        $totalNights = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })
        ->where('status', 'confirmed')
        ->where('check_in', '>=', Carbon::now()->subDays(30))
        ->get()
        ->sum(function($booking) {
            return Carbon::parse($booking->check_in)->diffInDays(Carbon::parse($booking->check_out));
        });

        $totalRooms = Accommodation::where('host_id', $hostId)
            ->withCount('rooms')
            ->get()
            ->sum('rooms_count');

        $availableNights = $totalRooms * 30;
        $occupancyRate = $availableNights > 0 ? ($totalNights / $availableNights) * 100 : 0;

        // Top performing accommodations
        $topAccommodations = Accommodation::where('host_id', $hostId)
            ->withCount(['bookings' => function($q) {
                $q->where('status', 'confirmed');
            }])
            ->withSum(['bookings' => function($q) {
                $q->where('status', 'confirmed');
            }], 'total_price')
            ->orderBy('bookings_sum_total_price', 'desc')
            ->limit(5)
            ->get();

        // Average rating trend
        $ratingTrend = Accommodation::where('host_id', $hostId)
            ->select('rating', 'created_at')
            ->orderBy('created_at')
            ->get();

        // Accommodation status statistics
        $totalAccommodations = Accommodation::where('host_id', $hostId)->count();
        $publishedAccommodations = Accommodation::where('host_id', $hostId)
            ->where('status', 'published')
            ->count();
        $pendingAccommodations = Accommodation::where('host_id', $hostId)
            ->where('status', 'pending')
            ->count();
        $rejectedAccommodations = Accommodation::where('host_id', $hostId)
            ->where('status', 'rejected')
            ->count();

        // Upcoming bookings (next 30 days)
        $upcomingBookings = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })
        ->where('status', 'confirmed')
        ->where('check_in', '>=', Carbon::now())
        ->where('check_in', '<=', Carbon::now()->addDays(30))
        ->count();

        // Pending bookings (awaiting confirmation)
        $pendingBookings = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })
        ->where('status', 'pending')
        ->count();

        // Revenue this month
        $revenueThisMonth = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })
        ->where('status', 'confirmed')
        ->whereMonth('created_at', Carbon::now()->month)
        ->whereYear('created_at', Carbon::now()->year)
        ->sum('total_price');

        // Revenue last month
        $revenueLastMonth = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })
        ->where('status', 'confirmed')
        ->whereMonth('created_at', Carbon::now()->subMonth()->month)
        ->whereYear('created_at', Carbon::now()->subMonth()->year)
        ->sum('total_price');

        $revenueGrowth = $revenueLastMonth > 0 
            ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100 
            : 0;

        // Daily revenue (today)
        $dailyRevenue = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })
        ->where('status', 'confirmed')
        ->whereDate('created_at', Carbon::today())
        ->sum('total_price');

        // Weekly revenue (last 7 days)
        $weeklyRevenue = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })
        ->where('status', 'confirmed')
        ->where('created_at', '>=', Carbon::now()->subDays(7))
        ->sum('total_price');

        // Monthly revenue (current month)
        $monthlyRevenueCurrent = Booking::whereHas('accommodation', function($q) use ($hostId) {
            $q->where('host_id', $hostId);
        })
        ->where('status', 'confirmed')
        ->whereMonth('created_at', Carbon::now()->month)
        ->whereYear('created_at', Carbon::now()->year)
        ->sum('total_price');

        // Statistics per accommodation
        $accommodationsStats = Accommodation::where('host_id', $hostId)
            ->withCount(['bookings' => function($q) {
                $q->where('status', 'confirmed');
            }])
            ->withSum(['bookings' => function($q) {
                $q->where('status', 'confirmed');
            }], 'total_price')
            ->withCount(['bookings as daily_bookings' => function($q) {
                $q->where('status', 'confirmed')
                  ->whereDate('created_at', Carbon::today());
            }])
            ->withSum(['bookings as daily_revenue' => function($q) {
                $q->where('status', 'confirmed')
                  ->whereDate('created_at', Carbon::today());
            }], 'total_price')
            ->withCount(['bookings as weekly_bookings' => function($q) {
                $q->where('status', 'confirmed')
                  ->where('created_at', '>=', Carbon::now()->subDays(7));
            }])
            ->withSum(['bookings as weekly_revenue' => function($q) {
                $q->where('status', 'confirmed')
                  ->where('created_at', '>=', Carbon::now()->subDays(7));
            }], 'total_price')
            ->withCount(['bookings as monthly_bookings' => function($q) {
                $q->where('status', 'confirmed')
                  ->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
            }])
            ->withSum(['bookings as monthly_revenue' => function($q) {
                $q->where('status', 'confirmed')
                  ->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
            }], 'total_price')
            ->get()
            ->map(function($accommodation) {
                return [
                    'id' => $accommodation->id,
                    'name' => $accommodation->name,
                    'city' => $accommodation->city,
                    'type' => $accommodation->type,
                    'status' => $accommodation->status,
                    'total_bookings' => $accommodation->bookings_count ?? 0,
                    'total_revenue' => (float) ($accommodation->bookings_sum_total_price ?? 0),
                    'daily_bookings' => $accommodation->daily_bookings ?? 0,
                    'daily_revenue' => (float) ($accommodation->daily_revenue ?? 0),
                    'weekly_bookings' => $accommodation->weekly_bookings ?? 0,
                    'weekly_revenue' => (float) ($accommodation->weekly_revenue ?? 0),
                    'monthly_bookings' => $accommodation->monthly_bookings ?? 0,
                    'monthly_revenue' => (float) ($accommodation->monthly_revenue ?? 0),
                ];
            });

        return response()->json([
            'total_bookings' => $totalBookings,
            'confirmed_bookings' => $confirmedBookings,
            'pending_bookings' => $pendingBookings,
            'upcoming_bookings' => $upcomingBookings,
            'total_revenue' => $totalRevenue,
            'revenue_this_month' => $revenueThisMonth,
            'revenue_last_month' => $revenueLastMonth,
            'revenue_growth' => round($revenueGrowth, 2),
            'daily_revenue' => $dailyRevenue,
            'weekly_revenue' => $weeklyRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'monthly_revenue_current' => $monthlyRevenueCurrent,
            'occupancy_rate' => round($occupancyRate, 2),
            'top_accommodations' => $topAccommodations,
            'accommodations_stats' => $accommodationsStats,
            'rating_trend' => $ratingTrend,
            'accommodations' => [
                'total' => $totalAccommodations,
                'published' => $publishedAccommodations,
                'pending' => $pendingAccommodations,
                'rejected' => $rejectedAccommodations,
            ],
        ]);
    }

    public function adminDashboard(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $stats = [
            'total_users' => User::count(),
            'total_hosts' => User::where('role', 'host')->count(),
            'total_accommodations' => Accommodation::count(),
            'published_accommodations' => Accommodation::where('status', 'published')->count(),
            'total_bookings' => Booking::count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'total_revenue' => Booking::where('status', 'confirmed')->sum('total_price'),
            'subscription_revenue' => Subscription::where('status', 'active')->sum('amount_paid'),
            'active_subscriptions' => Subscription::where('status', 'active')
                ->where('expires_at', '>=', Carbon::now())
                ->count(),
        ];

        // Active cities
        $cities = Accommodation::select('city', DB::raw('count(*) as count'))
            ->where('status', 'published')
            ->groupBy('city')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Monthly bookings trend
        $monthlyBookings = Booking::where('created_at', '>=', Carbon::now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'stats' => $stats,
            'cities' => $cities,
            'monthly_bookings' => $monthlyBookings,
        ]);
    }

    public function travelerDashboard(Request $request)
    {
        if (!$request->user()->isUser()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $userId = $request->user()->id;

        $bookings = Booking::where('user_id', $userId)
            ->with(['accommodation.images', 'room'])
            ->orderBy('created_at', 'desc')
            ->get();

        $upcomingBookings = $bookings->filter(function($booking) {
            return $booking->check_in >= Carbon::now() && $booking->status === 'confirmed';
        })->values();

        $pastBookings = $bookings->filter(function($booking) {
            return $booking->check_out < Carbon::now() && $booking->status === 'confirmed';
        })->values();

        // Top rated accommodations (for recommendations)
        $topRated = Accommodation::published()
            ->where('rating', '>=', 4)
            ->orderBy('rating', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_bookings' => $bookings->count(),
            'upcoming_bookings' => $upcomingBookings,
            'past_bookings' => $pastBookings,
            'recommendations' => $topRated,
        ]);
    }

    public function accommodationStats(Request $request, $accommodationId)
    {
        if (!$request->user()->isHost()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $hostId = $request->user()->id;

        // Vérifier que l'établissement appartient à l'hôte
        $accommodation = Accommodation::where('id', $accommodationId)
            ->where('host_id', $hostId)
            ->firstOrFail();

        // Total bookings
        $totalBookings = Booking::where('accommodation_id', $accommodationId)->count();
        $confirmedBookings = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'confirmed')
            ->count();
        $pendingBookings = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'pending')
            ->count();

        // Total revenue
        $totalRevenue = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'confirmed')
            ->sum('total_price');

        // Daily revenue (today)
        $dailyRevenue = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'confirmed')
            ->whereDate('created_at', Carbon::today())
            ->sum('total_price');
        $dailyBookings = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'confirmed')
            ->whereDate('created_at', Carbon::today())
            ->count();

        // Weekly revenue (last 7 days)
        $weeklyRevenue = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'confirmed')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->sum('total_price');
        $weeklyBookings = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'confirmed')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();

        // Monthly revenue (current month)
        $monthlyRevenue = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'confirmed')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_price');
        $monthlyBookings = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'confirmed')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // Revenue last month
        $revenueLastMonth = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'confirmed')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->sum('total_price');

        $revenueGrowth = $revenueLastMonth > 0 
            ? (($monthlyRevenue - $revenueLastMonth) / $revenueLastMonth) * 100 
            : 0;

        // Monthly revenue trend (last 6 months)
        $monthlyRevenueTrend = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'confirmed')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(total_price) as revenue'),
                DB::raw('COUNT(*) as bookings')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Upcoming bookings (next 30 days)
        $upcomingBookings = Booking::where('accommodation_id', $accommodationId)
            ->where('status', 'confirmed')
            ->where('check_in', '>=', Carbon::now())
            ->where('check_in', '<=', Carbon::now()->addDays(30))
            ->count();

        // Recent bookings (last 10)
        $recentBookings = Booking::where('accommodation_id', $accommodationId)
            ->with(['user:id,name,email', 'room:id,name,type'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'check_in' => $booking->check_in,
                    'check_out' => $booking->check_out,
                    'guests' => $booking->guests,
                    'total_price' => $booking->total_price,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at,
                    'user' => $booking->user ? [
                        'name' => $booking->user->name,
                        'email' => $booking->user->email,
                    ] : null,
                    'room' => $booking->room ? [
                        'name' => $booking->room->name,
                        'type' => $booking->room->type,
                    ] : null,
                ];
            });

        return response()->json([
            'accommodation' => [
                'id' => $accommodation->id,
                'name' => $accommodation->name,
                'type' => $accommodation->type,
                'city' => $accommodation->city,
                'status' => $accommodation->status,
            ],
            'total_bookings' => $totalBookings,
            'confirmed_bookings' => $confirmedBookings,
            'pending_bookings' => $pendingBookings,
            'upcoming_bookings' => $upcomingBookings,
            'total_revenue' => $totalRevenue,
            'daily_revenue' => $dailyRevenue,
            'daily_bookings' => $dailyBookings,
            'weekly_revenue' => $weeklyRevenue,
            'weekly_bookings' => $weeklyBookings,
            'monthly_revenue' => $monthlyRevenue,
            'monthly_bookings' => $monthlyBookings,
            'revenue_last_month' => $revenueLastMonth,
            'revenue_growth' => round($revenueGrowth, 2),
            'monthly_revenue_trend' => $monthlyRevenueTrend,
            'recent_bookings' => $recentBookings,
        ]);
    }
}
