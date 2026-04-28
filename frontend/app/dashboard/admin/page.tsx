'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/stores/authStore';
import { isController, isAdmin } from '@/lib/userUtils';
import api from '@/lib/api';
import Header from '@/components/common/Header';
import LoadingSpinner from '@/components/common/LoadingSpinner';
import RoomStatsCard from '@/components/dashboard/RoomStatsCard';
import Link from 'next/link';
import {
  Users,
  Home,
  UserCheck,
  Building2,
  Calendar,
  DollarSign,
  TrendingUp,
  AlertCircle,
  CheckCircle,
  XCircle,
  Clock,
  FileCheck,
  BarChart3,
  Wallet,
} from 'lucide-react';
import { formatPrice } from '@/lib/utils';
import DateRangeFilter, { useDefaultDateRange } from '@/components/common/DateRangeFilter';

interface RoomStatsAdmin {
  total_rooms: number;
  active_rooms: number;
  inactive_rooms: number;
  rooms_needing_images: number;
  avg_images_per_room: number;
  total_room_images?: number;
}

interface DashboardStats {
  users: {
    total: number;
    active: number;
    blocked: number;
    new: number;
  };
  hosts: {
    total: number;
    verified: number;
    pending: number;
    rejected: number;
  };
  accommodations: {
    total: number;
    published: number;
    pending: number;
    rejected: number;
    removed: number;
    disabled: number;
  };
  bookings: {
    total: number;
    confirmed: number;
    cancelled: number;
    pending?: number;
    modified?: number;
    new: number;
  };
  accounting?: {
    commissions_due: number;
    commissions_reversed: number;
    platform_commissions_total?: number;
    platform_commissions_pending?: number;
    platform_commissions_paid?: number;
  };
  promotions?: {
    active_count: number;
  };
  kpis?: {
    revpar: number;
    total_revenue: number;
    occupancy_rate: number;
    average_price_per_room: number;
    period_days?: number;
  };
  revenue: {
    period: number;
    all_time: number;
    period_days: number;
  };
  inspections: {
    total: number;
    completed: number;
    approved: number;
    rejected: number;
  };
  room_stats?: RoomStatsAdmin;
}

export default function AdminDashboardPage() {
  const router = useRouter();
  const { user, isAuthenticated, isLoading } = useAuthStore();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const defaultRange = useDefaultDateRange(30);
  const [dateRange, setDateRange] = useState(defaultRange);

  useEffect(() => {
    if (!isLoading && isAuthenticated && user) {
      // Rediriger les contrôleurs vers leur page d'inspections (vérification via RBAC)
      if (isController(user)) {
        router.push('/dashboard/admin/inspections');
        return;
      }
      // Rediriger les non-admins vers la page de login
      if (!isAdmin(user)) {
        router.push('/auth/login');
      }
    }
  }, [isAuthenticated, isLoading, user, router]);

  useEffect(() => {
    if (isAuthenticated && user && isAdmin(user)) {
      fetchStats();
    }
  }, [isAuthenticated, user, dateRange.from, dateRange.to]);

  const fetchStats = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await api.get('/admin/dashboard/stats', {
        params: { from_date: dateRange.from, to_date: dateRange.to },
      });
      if (response.data && response.data.data) {
        setStats(response.data.data);
      } else {
        setError('Format de réponse invalide');
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erreur lors du chargement des statistiques');
      console.error('Error fetching stats:', err);
    } finally {
      setLoading(false);
    }
  };

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

  if (!isAuthenticated || !user || !isAdmin(user)) {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <Header />
      <main className="container mx-auto px-4 py-8">
        <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
              Tableau de bord Administrateur
            </h1>
            <p className="text-gray-600 dark:text-gray-400">
              Vue d'ensemble de la plateforme Bosejour
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
          <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
            <p className="text-red-800 dark:text-red-400">{error}</p>
          </div>
        )}

        {stats && (
          <>
            {/* Statistiques principales */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              {/* Utilisateurs */}
              <div className="card">
                <div className="flex items-center justify-between mb-4">
                  <div className="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <Users className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                  </div>
                  <Link
                    href="/dashboard/admin/users"
                    className="text-sm text-primary hover:underline"
                  >
                    Voir tout
                  </Link>
                </div>
                <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                  Utilisateurs
                </h3>
                <p className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                  {stats.users.total}
                </p>
                <div className="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400">
                  <span className="flex items-center gap-1">
                    <CheckCircle className="w-3 h-3 text-green-500" />
                    {stats.users.active} actifs
                  </span>
                  <span className="flex items-center gap-1">
                    <XCircle className="w-3 h-3 text-red-500" />
                    {stats.users.blocked} bloqués
                  </span>
                </div>
              </div>

              {/* Hôtes */}
              <div className="card">
                <div className="flex items-center justify-between mb-4">
                  <div className="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <UserCheck className="w-6 h-6 text-green-600 dark:text-green-400" />
                  </div>
                  <Link
                    href="/dashboard/admin/hosts"
                    className="text-sm text-primary hover:underline"
                  >
                    Voir tout
                  </Link>
                </div>
                <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                  Hôtes
                </h3>
                <p className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                  {stats.hosts.total}
                </p>
                <div className="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400">
                  <span className="flex items-center gap-1">
                    <CheckCircle className="w-3 h-3 text-green-500" />
                    {stats.hosts.verified} validés
                  </span>
                  <span className="flex items-center gap-1">
                    <Clock className="w-3 h-3 text-yellow-500" />
                    {stats.hosts.pending} en attente
                  </span>
                </div>
              </div>

              {/* Établissements */}
              <div className="card">
                <div className="flex items-center justify-between mb-4">
                  <div className="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <Building2 className="w-6 h-6 text-purple-600 dark:text-purple-400" />
                  </div>
                  <Link
                    href="/dashboard/admin/accommodations"
                    className="text-sm text-primary hover:underline"
                  >
                    Voir tout
                  </Link>
                </div>
                <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                  Établissements
                </h3>
                <p className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                  {stats.accommodations.total}
                </p>
                <div className="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400">
                  <span className="flex items-center gap-1">
                    <CheckCircle className="w-3 h-3 text-green-500" />
                    {stats.accommodations.published} publiés
                  </span>
                  <span className="flex items-center gap-1">
                    <Clock className="w-3 h-3 text-yellow-500" />
                    {stats.accommodations.pending} en attente
                  </span>
                </div>
              </div>

              {/* Revenus */}
              <div className="card">
                <div className="flex items-center justify-between mb-4">
                  <div className="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <DollarSign className="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                  </div>
                  <Link
                    href="/dashboard/admin/revenue"
                    className="text-sm text-primary hover:underline"
                  >
                    Voir tout
                  </Link>
                </div>
                <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                  Revenus ({stats.revenue.period_days}j)
                </h3>
                <p className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                  {formatPrice(stats.revenue.period)} FCFA
                </p>
                <p className="text-xs text-gray-600 dark:text-gray-400">
                  Total: {formatPrice(stats.revenue.all_time)} FCFA
                </p>
              </div>
            </div>

            {/* Statistiques des chambres */}
            {stats.room_stats && (
              <div className="mb-8">
                <RoomStatsCard stats={stats.room_stats} isAdmin={true} />
              </div>
            )}

            {/* Actions rapides */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
              <Link
                href="/dashboard/admin/users"
                className="card hover:shadow-lg transition-shadow"
              >
                <div className="flex items-center gap-4">
                  <div className="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <Users className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 dark:text-white">
                      Gérer les utilisateurs
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      Voir, bloquer, débloquer les utilisateurs
                    </p>
                  </div>
                </div>
              </Link>

              <Link
                href="/dashboard/admin/hosts"
                className="card hover:shadow-lg transition-shadow"
              >
                <div className="flex items-center gap-4">
                  <div className="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <UserCheck className="w-6 h-6 text-green-600 dark:text-green-400" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 dark:text-white">
                      Gérer les hôtes
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      Valider, rejeter, suspendre les hôtes
                    </p>
                  </div>
                </div>
              </Link>

              <Link
                href="/dashboard/admin/accommodations"
                className="card hover:shadow-lg transition-shadow"
              >
                <div className="flex items-center gap-4">
                  <div className="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <Building2 className="w-6 h-6 text-purple-600 dark:text-purple-400" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 dark:text-white">
                      Gérer les établissements
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      Approuver, rejeter, retirer les établissements
                    </p>
                  </div>
                </div>
              </Link>

              <Link
                href="/dashboard/admin/inspections"
                className="card hover:shadow-lg transition-shadow"
              >
                <div className="flex items-center gap-4">
                  <div className="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                    <FileCheck className="w-6 h-6 text-orange-600 dark:text-orange-400" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 dark:text-white">
                      Inspections
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      Gérer les inspections sur site
                    </p>
                  </div>
                </div>
              </Link>

              <Link
                href="/dashboard/admin/withdrawals"
                className="card hover:shadow-lg transition-shadow"
              >
                <div className="flex items-center gap-4">
                  <div className="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <Wallet className="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 dark:text-white">
                      Demandes de retrait
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      Approuver ou refuser les retraits des hôtes
                    </p>
                  </div>
                </div>
              </Link>

              <Link
                href="/dashboard/admin/revenue"
                className="card hover:shadow-lg transition-shadow"
              >
                <div className="flex items-center gap-4">
                  <div className="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <TrendingUp className="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 dark:text-white">
                      Revenus
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      Consulter les revenus et commissions
                    </p>
                  </div>
                </div>
              </Link>

              <Link
                href="/dashboard/admin/analytics"
                className="card hover:shadow-lg transition-shadow"
              >
                <div className="flex items-center gap-4">
                  <div className="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <BarChart3 className="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900 dark:text-white">
                      Analytics
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      Statistiques détaillées et graphiques
                    </p>
                  </div>
                </div>
              </Link>
            </div>

            {/* Réservations, Comptabilité, Inspections, KPI */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="card">
                <div className="flex items-center justify-between mb-4">
                  <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                    Réservations
                  </h2>
                  <Calendar className="w-5 h-5 text-gray-400" />
                </div>
                <div className="space-y-3">
                  <div className="flex justify-between items-center">
                    <span className="text-gray-600 dark:text-gray-400">Total</span>
                    <span className="font-semibold text-gray-900 dark:text-white">
                      {stats.bookings.total}
                    </span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-gray-600 dark:text-gray-400">Confirmées</span>
                    <span className="font-semibold text-green-600 dark:text-green-400">
                      {stats.bookings.confirmed}
                    </span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-gray-600 dark:text-gray-400">Annulées</span>
                    <span className="font-semibold text-red-600 dark:text-red-400">
                      {stats.bookings.cancelled}
                    </span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-gray-600 dark:text-gray-400">En attente</span>
                    <span className="font-semibold text-yellow-600 dark:text-yellow-400">
                      {stats.bookings.pending ?? 0}
                    </span>
                  </div>
                  {typeof stats.bookings.modified === 'number' && (
                    <div className="flex justify-between items-center">
                      <span className="text-gray-600 dark:text-gray-400">Modifiées</span>
                      <span className="font-semibold text-gray-900 dark:text-white">
                        {stats.bookings.modified}
                      </span>
                    </div>
                  )}
                  <div className="flex justify-between items-center">
                    <span className="text-gray-600 dark:text-gray-400">Nouvelles</span>
                    <span className="font-semibold text-blue-600 dark:text-blue-400">
                      {stats.bookings.new}
                    </span>
                  </div>
                </div>
              </div>

              {stats.accounting && (
                <div className="card">
                  <div className="flex items-center justify-between mb-4">
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                      Comptabilité
                    </h2>
                    <DollarSign className="w-5 h-5 text-gray-400" />
                  </div>
                  <div className="space-y-3">
                    <div className="flex justify-between items-center">
                      <span className="text-gray-600 dark:text-gray-400">Commissions dues (hôtels)</span>
                      <span className="font-semibold text-yellow-600 dark:text-yellow-400">
                        {formatPrice(stats.accounting.commissions_due)} FCFA
                      </span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-gray-600 dark:text-gray-400">Déjà reversées</span>
                      <span className="font-semibold text-green-600 dark:text-green-400">
                        {formatPrice(stats.accounting.commissions_reversed)} FCFA
                      </span>
                    </div>
                    {typeof stats.accounting.platform_commissions_total === 'number' && (
                      <div className="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span className="text-gray-600 dark:text-gray-400">Commission plateforme (total)</span>
                        <span className="font-semibold text-gray-900 dark:text-white">
                          {formatPrice(stats.accounting.platform_commissions_total)} FCFA
                        </span>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {(stats.promotions || stats.kpis) && (
                <div className="card md:col-span-3">
                  <div className="flex items-center justify-between mb-4">
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                      Promotions & KPI
                    </h2>
                    <BarChart3 className="w-5 h-5 text-gray-400" />
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {stats.promotions && (
                      <div>
                        <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Offres promotionnelles</h3>
                        <p className="text-2xl font-bold text-gray-900 dark:text-white">
                          {stats.promotions.active_count} promotion(s) active(s)
                        </p>
                      </div>
                    )}
                    {stats.kpis && (
                      <div className="space-y-2">
                        <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">Indicateurs ({stats.kpis.period_days ?? 30} jours)</h3>
                        <div className="flex justify-between text-sm">
                          <span className="text-gray-600 dark:text-gray-400">RevPAR</span>
                          <span className="font-semibold">{formatPrice(stats.kpis.revpar)} FCFA</span>
                        </div>
                        <div className="flex justify-between text-sm">
                          <span className="text-gray-600 dark:text-gray-400">Taux d&apos;occupation</span>
                          <span className="font-semibold">{stats.kpis.occupancy_rate} %</span>
                        </div>
                        <div className="flex justify-between text-sm">
                          <span className="text-gray-600 dark:text-gray-400">Prix moyen / chambre</span>
                          <span className="font-semibold">{formatPrice(stats.kpis.average_price_per_room)} FCFA</span>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              )}

              <div className="card">
                <div className="flex items-center justify-between mb-4">
                  <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                    Inspections
                  </h2>
                  <FileCheck className="w-5 h-5 text-gray-400" />
                </div>
                <div className="space-y-3">
                  <div className="flex justify-between items-center">
                    <span className="text-gray-600 dark:text-gray-400">Total</span>
                    <span className="font-semibold text-gray-900 dark:text-white">
                      {stats.inspections?.total ?? 0}
                    </span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-gray-600 dark:text-gray-400">Complétées</span>
                    <span className="font-semibold text-blue-600 dark:text-blue-400">
                      {stats.inspections?.completed ?? 0}
                    </span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-gray-600 dark:text-gray-400">Approuvées</span>
                    <span className="font-semibold text-green-600 dark:text-green-400">
                      {stats.inspections?.approved ?? 0}
                    </span>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-gray-600 dark:text-gray-400">Rejetées</span>
                    <span className="font-semibold text-red-600 dark:text-red-400">
                      {stats.inspections?.rejected ?? 0}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </>
        )}
      </main>
    </div>
  );
}

