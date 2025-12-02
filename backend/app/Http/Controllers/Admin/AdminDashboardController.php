<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Controller pour le dashboard admin avec analytics
 */
class AdminDashboardController extends Controller
{
    /**
     * Statistiques générales du dashboard
     */
    public function stats(Request $request)
    {
        $period = $request->get('period', '30'); // jours
        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        // Utilisateurs
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $blockedUsers = User::where('status', 'blocked')->count();
        $newUsers = User::where('created_at', '>=', $startDate)->count();

        // Hôtes
        $totalHosts = User::where('role', 'host')->count();
        $verifiedHosts = User::where('role', 'host')->where('profile_verified', true)->count();
        $pendingHosts = User::where('role', 'host')->where('profile_verified', false)->count();
        // Hôtes rejetés : ceux qui ont au moins une validation avec action 'rejected'
        $rejectedHosts = User::where('role', 'host')
            ->whereHas('hostValidationHistory', function ($q) {
                $q->where('action', 'rejected');
            })
            ->count();

        // Établissements
        $totalAccommodations = Accommodation::count();
        $publishedAccommodations = Accommodation::where('status', 'published')->count();
        $pendingAccommodations = Accommodation::where('status', 'pending')->count();
        $rejectedAccommodations = Accommodation::where('status', 'rejected')->count();
        $removedAccommodations = Accommodation::where('status', 'removed')->count();
        $disabledAccommodations = Accommodation::where('status', 'disabled')->count();

        // Réservations
        $totalBookings = Booking::count();
        $confirmedBookings = Booking::where('status', 'confirmed')->count();
        $cancelledBookings = Booking::where('status', 'cancelled')->count();
        $newBookings = Booking::where('created_at', '>=', $startDate)->count();

        // Revenus
        $totalRevenue = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');
        $totalRevenueAllTime = Payment::where('status', 'completed')->sum('amount');

        // Inspections
        $totalInspections = Inspection::count();
        $completedInspections = Inspection::where('status', 'completed')->count();
        $approvedInspections = Inspection::where('result', 'approved')->count();
        $rejectedInspections = Inspection::where('result', 'rejected')->count();

        return response()->json([
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'blocked' => $blockedUsers,
                    'new' => $newUsers,
                ],
                'hosts' => [
                    'total' => $totalHosts,
                    'verified' => $verifiedHosts,
                    'pending' => $pendingHosts,
                    'rejected' => $rejectedHosts,
                ],
                'accommodations' => [
                    'total' => $totalAccommodations,
                    'published' => $publishedAccommodations,
                    'pending' => $pendingAccommodations,
                    'rejected' => $rejectedAccommodations,
                    'removed' => $removedAccommodations,
                    'disabled' => $disabledAccommodations,
                ],
                'bookings' => [
                    'total' => $totalBookings,
                    'confirmed' => $confirmedBookings,
                    'cancelled' => $cancelledBookings,
                    'new' => $newBookings,
                ],
                'revenue' => [
                    'period' => $totalRevenue,
                    'all_time' => $totalRevenueAllTime,
                    'period_days' => $period,
                ],
                'inspections' => [
                    'total' => $totalInspections,
                    'completed' => $completedInspections,
                    'approved' => $approvedInspections,
                    'rejected' => $rejectedInspections,
                ],
            ],
        ]);
    }

    /**
     * Graphique d'activité journalière
     */
    public function dailyActivity(Request $request)
    {
        $period = $request->get('period', '30');
        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        $data = [];

        // Parcourir chaque jour
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $data[] = [
                'date' => $currentDate->format('Y-m-d'),
                'users' => User::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'bookings' => Booking::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'accommodations' => Accommodation::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'revenue' => Payment::where('status', 'completed')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('amount'),
            ];

            $currentDate->addDay();
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Graphique des performances des hôtes
     */
    public function hostPerformance(Request $request)
    {
        $limit = $request->get('limit', 10);

        $hosts = User::where('role', 'host')
            ->withCount([
                'accommodations',
                'bookings',
            ])
            ->withSum('accommodations', 'total_reviews')
            ->orderBy('accommodations_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($host) {
                return [
                    'id' => $host->id,
                    'name' => $host->name,
                    'establishment_name' => $host->establishment_name,
                    'accommodations_count' => $host->accommodations_count,
                    'bookings_count' => $host->bookings_count,
                    'total_reviews' => $host->accommodations_sum_total_reviews ?? 0,
                ];
            });

        return response()->json(['data' => $hosts]);
    }

    /**
     * Répartition des états des biens
     */
    public function accommodationStatusDistribution()
    {
        $distribution = Accommodation::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                ];
            });

        return response()->json(['data' => $distribution]);
    }
}

