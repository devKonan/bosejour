'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/stores/authStore';
import { useConfirm } from '@/components/common/ConfirmContext';
import api from '@/lib/api';
import Header from '@/components/common/Header';
import LoadingSpinner from '@/components/common/LoadingSpinner';
import ErrorDisplay from '@/components/common/ErrorDisplay';
import Pagination from '@/components/common/Pagination';
import RoomStatsCard from '@/components/dashboard/RoomStatsCard';
import { formatPrice } from '@/lib/utils';
import DateRangeFilter, { useDefaultDateRange } from '@/components/common/DateRangeFilter';
import Link from 'next/link';
import Image from 'next/image';
import { 
  Calendar, 
  TrendingUp, 
  DollarSign, 
  Home, 
  Clock, 
  CheckCircle, 
  XCircle,
  Eye,
  Plus,
  BarChart3,
  Users,
  Star,
  AlertCircle,
  Bed,
  Trash2
} from 'lucide-react';

interface RoomTypePricing {
  type: string;
  price_per_night: number;
  rooms_available?: number | null;
}

interface Accommodation {
  id: number;
  name: string;
  type: string;
  city: string;
  status: 'pending' | 'published' | 'rejected' | 'unavailable' | 'renovation';
  price_per_night: number;
  room_type_pricing?: RoomTypePricing[];
  images?: Array<{ id: number; url: string; is_primary?: boolean }>;
  bookings_count?: number;
  rating?: number | null;
  total_reviews?: number | null;
  created_at: string;
  // Statistiques de chambres
  total_rooms_count?: number;
  active_rooms_count?: number;
  inactive_rooms_count?: number;
}

interface AccommodationStats {
  id: number;
  name: string;
  city: string;
  type: string;
  status: string;
  total_bookings: number;
  total_revenue: number;
  daily_bookings?: number;
  daily_revenue?: number;
  weekly_bookings?: number;
  weekly_revenue?: number;
  monthly_bookings?: number;
  monthly_revenue?: number;
  period_bookings?: number;
  period_revenue?: number;
}

interface RoomStats {
  total_rooms: number;
  active_rooms: number;
  inactive_rooms: number;
  rooms_needing_images: number;
  avg_images_per_room: number;
  room_bookings_last_30_days?: number;
}

interface AnalyticsData {
  total_bookings: number;
  confirmed_bookings: number;
  pending_bookings: number;
  upcoming_bookings: number;
  total_revenue: number;
  revenue_this_month: number;
  revenue_last_month: number;
  revenue_growth: number;
  daily_revenue?: number;
  weekly_revenue?: number;
  monthly_revenue_current?: number;
  occupancy_rate: number;
  room_stats?: RoomStats;
  accommodations: {
    total: number;
    published: number;
    pending: number;
    rejected: number;
  };
  top_accommodations?: Array<{
    id: number;
    name: string;
    bookings_sum_total_price: number;
    bookings_count: number;
  }>;
  accommodations_stats?: AccommodationStats[];
  monthly_revenue?: Array<{
    month: string;
    revenue: number;
  }>;
  reservations?: { total: number; modified: number; pending: number };
  accounting?: { commissions_due: number; commissions_reversed: number };
  promotions?: { active_count: number };
  kpis?: {
    revpar: number;
    total_revenue: number;
    occupancy_rate: number;
    average_price_per_room: number;
    period_days?: number;
  };
}

export default function HostDashboardPage() {
  const router = useRouter();
  const { user, isAuthenticated, isLoading } = useAuthStore();
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);
  const [accommodations, setAccommodations] = useState<Accommodation[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [statusFilter, setStatusFilter] = useState<'all' | 'pending' | 'published' | 'rejected' | 'unavailable' | 'renovation'>('all');
  const [currentPage, setCurrentPage] = useState(1);
  const [pagination, setPagination] = useState({
    total: 0,
    per_page: 10,
    current_page: 1,
    last_page: 1,
  });
  const [updatingId, setUpdatingId] = useState<number | null>(null);
  const [appointmentAccId, setAppointmentAccId] = useState<number | null>(null);
  const [appointmentDate, setAppointmentDate] = useState<string>('');
  const [appointmentNotes, setAppointmentNotes] = useState<string>('');
  const defaultRange = useDefaultDateRange(30);
  const [dateRange, setDateRange] = useState(defaultRange);
  const confirmAction = useConfirm();

  useEffect(() => {
    if (!isLoading && (!isAuthenticated || user?.role !== 'host')) {
      router.push('/auth/login');
    }
  }, [isAuthenticated, isLoading, user, router]);

  useEffect(() => {
    if (isAuthenticated && user?.role === 'host') {
      fetchData();
    }
  }, [isAuthenticated, user, statusFilter, currentPage, dateRange.from, dateRange.to]);

  const fetchData = async () => {
    try {
      setLoading(true);
      setError(null);

      const params = new URLSearchParams();
      params.append('per_page', '10');
      params.append('page', currentPage.toString());
      if (statusFilter !== 'all') {
        params.append('status', statusFilter);
      }

      const [analyticsRes, accommodationsRes] = await Promise.all([
        api.get('/analytics/host', { params: { from_date: dateRange.from, to_date: dateRange.to } }),
        api.get(`/accommodations/my?${params.toString()}`)
      ]);

      setAnalytics(analyticsRes.data);
      
      // Gérer la réponse paginée
      if (accommodationsRes.data.data && Array.isArray(accommodationsRes.data.data)) {
        setAccommodations(accommodationsRes.data.data);
        setPagination({
          total: accommodationsRes.data.total || 0,
          per_page: accommodationsRes.data.per_page || 10,
          current_page: accommodationsRes.data.current_page || 1,
          last_page: accommodationsRes.data.last_page || 1,
        });
      } else if (Array.isArray(accommodationsRes.data)) {
        setAccommodations(accommodationsRes.data);
        setPagination({
          total: accommodationsRes.data.length,
          per_page: 10,
          current_page: 1,
          last_page: 1,
        });
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erreur lors du chargement des données');
    } finally {
      setLoading(false);
    }
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Réinitialiser à la page 1 quand le filtre change
  useEffect(() => {
    setCurrentPage(1);
  }, [statusFilter]);

  const changeStatus = async (id: number, status: Accommodation['status']) => {
    try {
      setUpdatingId(id);
      await api.put(`/accommodations/${id}`, { status });
      await fetchData();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erreur lors de la mise à jour du statut');
    } finally {
      setUpdatingId(null);
    }
  };

  const deleteAccommodation = async (id: number) => {
    const ok = await confirmAction({
      title: 'Supprimer l\'hébergement',
      message: 'Supprimer définitivement cet hébergement ? Cette action est irréversible.',
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
      variant: 'danger',
    });
    if (!ok) return;
    try {
      setUpdatingId(id);
      await api.delete(`/accommodations/${id}`);
      await fetchData();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erreur lors de la suppression');
    } finally {
      setUpdatingId(null);
    }
  };

  const submitAppointment = async () => {
    if (!appointmentAccId || !appointmentDate) return;
    try {
      setUpdatingId(appointmentAccId);
      await api.post(`/accommodations/${appointmentAccId}/appointments`, {
        requested_at: appointmentDate,
        notes: appointmentNotes,
      });
      setAppointmentAccId(null);
      setAppointmentDate('');
      setAppointmentNotes('');
      alert('Demande de visite envoyée.');
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erreur lors de la demande de visite');
    } finally {
      setUpdatingId(null);
    }
  };

  const statusLabels = {
    pending: { label: 'En attente', color: 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-400', icon: Clock },
    published: { label: 'Publié', color: 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400', icon: CheckCircle },
    rejected: { label: 'Rejeté', color: 'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400', icon: XCircle },
    unavailable: { label: 'Indisponible', color: 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300', icon: Clock },
    renovation: { label: 'Rénovation', color: 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-400', icon: Clock },
    disabled: { label: 'Désactivé', color: 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300', icon: Clock },
    removed: { label: 'Retiré', color: 'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-400', icon: XCircle },
  };

  const typeLabels: Record<string, string> = {
    hotel: 'Hôtel',
    lodge: 'Lodge',
    guesthouse: 'Maison d\'hôtes',
    apartment: 'Appartement',
  };

  const filteredAccommodations = statusFilter === 'all' 
    ? accommodations 
    : accommodations.filter(acc => acc.status === statusFilter);

  if (isLoading || loading) {
    return (
      <div className="min-h-screen">
        <Header />
        <div className="container mx-auto px-4 py-8">
          <LoadingSpinner />
        </div>
      </div>
    );
  }

  if (!isAuthenticated || user?.role !== 'host') {
    return null;
  }

  return (
    <div className="min-h-screen">
      <Header />
      
      <main className="container mx-auto px-4 py-8 max-w-7xl">
        <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
          <div>
            <div className="flex items-center gap-4 flex-wrap mb-2">
              <h1 className="text-4xl font-bold text-primary">Tableau de bord Hôte</h1>
              <Link 
                href="/dashboard/host/accommodations/new"
                className="btn-primary flex items-center gap-2"
              >
                <Plus className="w-5 h-5" />
                Ajouter un hébergement
              </Link>
            </div>
            <p className="text-gray-600 dark:text-gray-400">
              Gérez vos hébergements et suivez vos performances
            </p>
          </div>
          <DateRangeFilter
            from={dateRange.from}
            to={dateRange.to}
            onRangeChange={(from, to) => setDateRange({ from, to })}
            label="Période"
          />
        </div>

        {error && (
          <ErrorDisplay error={error} onDismiss={() => setError(null)} />
        )}

        {analytics && (
          <>
            {/* Alertes rapides liées aux réservations / validations */}
            {analytics.pending_bookings > 0 && (
              <div className="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <AlertCircle className="w-5 h-5 text-yellow-600 flex-shrink-0" />
                  <p className="text-yellow-800 dark:text-yellow-400">
                    Vous avez <strong>{analytics.pending_bookings}</strong> réservation(s) en attente de confirmation
                  </p>
                </div>
                <Link
                  href="/dashboard/host/bookings/requests"
                  className="btn-primary text-sm"
                >
                  Voir les demandes
                </Link>
              </div>
            )}

            {analytics.accommodations.pending > 0 && (
              <div className="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-center gap-3">
                <Clock className="w-5 h-5 text-blue-600 flex-shrink-0" />
                <p className="text-blue-800 dark:text-blue-400">
                  Vous avez <strong>{analytics.accommodations.pending}</strong> hébergement(s) en attente de validation par l'administrateur
                </p>
              </div>
            )}

            {/* Revenus (période) */}
            {(analytics.accounting || analytics.total_revenue !== undefined) && (
              <div className="mb-6">
                <div className="card">
                  <h3 className="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <DollarSign className="w-5 h-5 text-primary" />
                    Revenus
                  </h3>
                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-gray-600 dark:text-gray-400">Chiffre d&apos;affaires (période)</span>
                      <span className="font-semibold text-primary">
                        {formatPrice(analytics.total_revenue ?? 0)} FCFA
                      </span>
                    </div>
                    {analytics.accounting && (
                      <>
                        <div className="flex justify-between">
                          <span className="text-gray-600 dark:text-gray-400">Montants à vous reverser</span>
                          <span className="font-semibold text-yellow-600 dark:text-yellow-400">
                            {formatPrice(analytics.accounting.commissions_due)} FCFA
                          </span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-600 dark:text-gray-400">Déjà reversés</span>
                          <span className="font-semibold text-green-600 dark:text-green-400">
                            {formatPrice(analytics.accounting.commissions_reversed)} FCFA
                          </span>
                        </div>
                      </>
                    )}
                  </div>
                </div>
              </div>
            )}

            {/* Statistiques des chambres */}
            {analytics.room_stats && (
              <div className="mb-8">
                <RoomStatsCard stats={analytics.room_stats} />
              </div>
            )}
          </>
        )}

        {/* Liste des hébergements */}
        <div className="mb-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-2xl font-bold">Mes hébergements</h2>
            <div className="flex gap-2">
              {(['all', 'published', 'pending', 'rejected'] as const).map((status) => (
                <button
                  key={status}
                  onClick={() => setStatusFilter(status)}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                    statusFilter === status
                      ? 'bg-primary text-white'
                      : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'
                  }`}
                >
                  {status === 'all' ? 'Tous' : statusLabels[status].label}
                </button>
              ))}
            </div>
          </div>

          {filteredAccommodations.length === 0 ? (
            <div className="card text-center py-12">
              <Home className="w-16 h-16 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600 dark:text-gray-400 mb-4">
                {statusFilter === 'all' 
                  ? 'Vous n\'avez pas encore d\'hébergement'
                  : `Aucun hébergement ${statusLabels[statusFilter as keyof typeof statusLabels].label.toLowerCase()}`
                }
              </p>
              {statusFilter === 'all' && (
                <Link href="/dashboard/host/accommodations/new" className="btn-primary inline-flex items-center gap-2">
                  <Plus className="w-5 h-5" />
                  Ajouter votre premier hébergement
                </Link>
              )}
            </div>
          ) : (
            <>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {filteredAccommodations.map((acc) => {
                  const StatusIcon = statusLabels[acc.status as keyof typeof statusLabels]?.icon || Clock;
                  const primaryImage = acc.images?.find(img => img.is_primary)?.url || 
                                     acc.images?.[0]?.url || 
                                     'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800';

                  return (
                    <div key={acc.id} className="card hover:shadow-lg transition-shadow">
                      <Link href={`/accommodations/${acc.id}`}>
                        <div className="relative h-48 mb-4 rounded-lg overflow-hidden">
                          <Image
                            src={primaryImage}
                            alt={acc.name}
                            fill
                            className="object-cover"
                          />
                          <div className="absolute top-2 right-2">
                            <span className={`px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1 ${statusLabels[acc.status as keyof typeof statusLabels]?.color || 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'}`}>
                              <StatusIcon className="w-3 h-3" />
                              {statusLabels[acc.status as keyof typeof statusLabels]?.label || acc.status}
                            </span>
                          </div>
                        </div>
                      </Link>

                      <div className="space-y-3">
                        <div>
                          <Link href={`/accommodations/${acc.id}`}>
                            <h3 className="font-semibold text-lg mb-1 hover:text-primary transition-colors">
                              {acc.name}
                            </h3>
                          </Link>
                          <p className="text-sm text-gray-600 dark:text-gray-400">
                            {typeLabels[acc.type] || acc.type} • {acc.city}
                          </p>
                        </div>

                        <div className="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                          <div>
                            <p className="text-2xl font-bold text-primary">
                              {formatPrice(acc.price_per_night)} FCFA
                            </p>
                            <p className="text-xs text-gray-600 dark:text-gray-400">par nuit</p>
                            {/* Statistiques de chambres */}
                            {acc.total_rooms_count !== undefined && acc.total_rooms_count > 0 && (
                              <div className="flex items-center gap-3 mt-2 text-xs">
                                <span className="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                                  <Bed className="w-3 h-3" />
                                  {acc.total_rooms_count} chambre{acc.total_rooms_count > 1 ? 's' : ''}
                                </span>
                                {acc.active_rooms_count !== undefined && (
                                  <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                    <CheckCircle className="w-3 h-3" />
                                    {acc.active_rooms_count} active{acc.active_rooms_count > 1 ? 's' : ''}
                                  </span>
                                )}
                                {acc.inactive_rooms_count !== undefined && acc.inactive_rooms_count > 0 && (
                                  <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                                    <XCircle className="w-3 h-3" />
                                    {acc.inactive_rooms_count} inactive{acc.inactive_rooms_count > 1 ? 's' : ''}
                                  </span>
                                )}
                              </div>
                            )}
                            {acc.room_type_pricing && acc.room_type_pricing.length > 0 && !acc.total_rooms_count && (
                              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {acc.room_type_pricing.length} type{acc.room_type_pricing.length > 1 ? 's' : ''} de chambre{acc.room_type_pricing.length > 1 ? 's' : ''}
                              </p>
                            )}
                          </div>
                          <div className="text-right">
                            {acc.bookings_count !== undefined && (
                              <p className="text-sm font-medium flex items-center gap-1">
                                <Calendar className="w-4 h-4" />
                                {acc.bookings_count} réservation{acc.bookings_count > 1 ? 's' : ''}
                              </p>
                            )}
                            {acc.rating && typeof acc.rating === 'number' && acc.rating > 0 && (
                              <p className="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1 mt-1">
                                <Star className="w-4 h-4 fill-accent text-accent" />
                                {Number(acc.rating).toFixed(1)} ({acc.total_reviews || 0})
                              </p>
                            )}
                          </div>
                        </div>

                        <div className={`grid gap-2 pt-3 border-t border-gray-200 dark:border-gray-700 ${acc.status !== 'published' ? 'grid-cols-2 sm:grid-cols-3' : 'grid-cols-2'}`}>
                          <Link
                            href={`/accommodations/${acc.id}`}
                            className="btn-secondary text-center text-sm flex items-center justify-center gap-1"
                          >
                            <Eye className="w-4 h-4" />
                            Voir
                          </Link>
                          <Link
                            href={`/dashboard/host/accommodations/${acc.id}/rooms`}
                            className="btn-outline text-center text-sm flex items-center justify-center gap-1"
                          >
                            <Bed className="w-4 h-4" />
                            Chambres
                          </Link>
                          <Link
                            href={`/dashboard/host/accommodations/${acc.id}/stats`}
                            className="btn-outline text-center text-sm flex items-center justify-center gap-1"
                          >
                            <BarChart3 className="w-4 h-4" />
                            Stats
                          </Link>
                          <Link
                            href={`/dashboard/host/accommodations/${acc.id}/edit`}
                            className="btn-primary text-center text-sm flex items-center justify-center gap-1"
                          >
                            Modifier
                          </Link>
                          {acc.status !== 'published' && (
                            <button
                              type="button"
                              onClick={(e) => { e.preventDefault(); deleteAccommodation(acc.id); }}
                              disabled={updatingId === acc.id}
                              className="col-span-2 sm:col-span-1 text-center text-sm flex items-center justify-center gap-1 px-3 py-2 rounded-lg border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/30 font-medium disabled:opacity-50"
                            >
                              <Trash2 className="w-4 h-4" />
                              {updatingId === acc.id ? 'Suppression...' : 'Supprimer'}
                            </button>
                          )}
                        </div>

                        {/* Actions déplacées vers la page détail du bien */}
                      </div>
                    </div>
                  );
                })}
              </div>
              <Pagination
                currentPage={pagination.current_page}
                totalPages={pagination.last_page}
                onPageChange={handlePageChange}
                totalItems={pagination.total}
                itemsPerPage={pagination.per_page}
              />
            </>
          )}
        </div>

        {/* Revenus par établissement */}
        {analytics?.accommodations_stats && analytics.accommodations_stats.length > 0 && (
          <div className="mt-8">
            <h2 className="text-2xl font-bold mb-4">Revenus par établissement</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
              {analytics.accommodations_stats
                .sort((a, b) => (b.period_revenue ?? b.total_revenue) - (a.period_revenue ?? a.total_revenue))
                .map((acc) => (
                  <Link
                    key={acc.id}
                    href={`/dashboard/host/accommodations/${acc.id}/stats`}
                    className="card hover:shadow-lg transition-all border-l-4 border-primary cursor-pointer hover:border-primary-dark hover:scale-[1.02] group"
                  >
                    <div className="space-y-4">
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <h3 className="text-lg font-bold mb-1 group-hover:text-primary transition-colors">{acc.name}</h3>
                          <p className="text-sm text-gray-600 dark:text-gray-400">
                            {typeLabels[acc.type] || acc.type} • {acc.city}
                          </p>
                        </div>
                        <Eye className="w-5 h-5 text-gray-400 group-hover:text-primary transition-colors" />
                      </div>
                      
                      <div className="space-y-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div>
                          <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Revenus (période)</p>
                          <p className="text-2xl font-bold text-primary">
                            {formatPrice(acc.period_revenue ?? acc.total_revenue)} FCFA
                          </p>
                          <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            {(acc.period_bookings ?? acc.total_bookings)} réservation{(acc.period_bookings ?? acc.total_bookings) !== 1 ? 's' : ''}
                          </p>
                        </div>
                      </div>
                    </div>
                  </Link>
                ))}
            </div>
          </div>
        )}

        {/* Statistiques détaillées par établissement */}
        {analytics?.accommodations_stats && analytics.accommodations_stats.length > 0 && (
          <div className="mt-8">
            <h2 className="text-2xl font-bold mb-4">Statistiques détaillées par établissement</h2>
            <div className="card overflow-x-auto">
              <div className="min-w-full">
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-gray-200 dark:border-gray-700">
                      <th className="text-left py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Établissement</th>
                      <th className="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Réservations</th>
                      <th className="text-right py-3 px-4 font-semibold text-gray-700 dark:text-gray-300">Revenus (période)</th>
                    </tr>
                  </thead>
                  <tbody>
                    {analytics.accommodations_stats.map((acc) => (
                      <tr 
                        key={acc.id} 
                        className="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900 cursor-pointer"
                        onClick={() => router.push(`/dashboard/host/accommodations/${acc.id}/stats`)}
                      >
                        <td className="py-3 px-4">
                          <div>
                            <p className="font-semibold">{acc.name}</p>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                              {typeLabels[acc.type] || acc.type} • {acc.city}
                            </p>
                          </div>
                        </td>
                        <td className="py-3 px-4 text-right">
                          <p className="font-medium">{acc.period_bookings ?? acc.total_bookings}</p>
                        </td>
                        <td className="py-3 px-4 text-right">
                          <p className="font-bold text-primary">{formatPrice(acc.period_revenue ?? acc.total_revenue)} FCFA</p>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}

        {/* Top hébergements */}
        {analytics?.top_accommodations && analytics.top_accommodations.length > 0 && (
          <div className="mt-8">
            <h2 className="text-2xl font-bold mb-4">Top hébergements</h2>
            <div className="card">
              <div className="space-y-4">
                {analytics.top_accommodations.map((acc, index) => (
                  <Link
                    key={acc.id}
                    href={`/dashboard/host/accommodations/${acc.id}/stats`}
                    className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer group"
                  >
                    <div className="flex items-center gap-4 flex-1">
                      <div className="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                        {index + 1}
                      </div>
                      <div className="flex-1">
                        <p className="font-semibold group-hover:text-primary transition-colors">{acc.name}</p>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          {acc.bookings_count} réservation{acc.bookings_count > 1 ? 's' : ''}
                        </p>
                      </div>
                    </div>
                    <div className="text-right flex items-center gap-4">
                      <div>
                        <p className="text-lg font-bold text-primary">
                          {formatPrice(acc.bookings_sum_total_price || 0)} FCFA
                        </p>
                        <p className="text-xs text-gray-600 dark:text-gray-400">Chiffre d&apos;affaires</p>
                      </div>
                      <Eye className="w-5 h-5 text-gray-400 group-hover:text-primary transition-colors" />
                    </div>
                  </Link>
                ))}
              </div>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}

