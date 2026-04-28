'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useAuthStore } from '@/stores/authStore';
import { useConfirm } from '@/components/common/ConfirmContext';
import { isAdmin } from '@/lib/userUtils';
import api from '@/lib/api';
import Header from '@/components/common/Header';
import Footer from '@/components/common/Footer';
import LoadingSpinner from '@/components/common/LoadingSpinner';
import ErrorDisplay from '@/components/common/ErrorDisplay';
import { formatPrice } from '@/lib/utils';
import {
  ArrowLeft,
  Building2,
  Bed,
  Eye,
  EyeOff,
  Edit,
  Trash2,
  Image as ImageIcon,
  CheckCircle,
  XCircle,
  Calendar,
  Users,
  MapPin,
  DollarSign,
} from 'lucide-react';
import Link from 'next/link';
import Image from 'next/image';

interface Room {
  id: number;
  name: string;
  name_en?: string;
  type: string;
  description?: string;
  capacity: number;
  price_per_night: number;
  bedrooms: number;
  bathrooms: number;
  quantity?: number; // Nombre total de chambres de ce type
  is_active: boolean;
  surface_area?: number;
  room_category?: string;
  room_subcategory?: string;
  images?: Array<{
    id: number;
    full_url: string;
    thumbnail_url: string;
    is_primary: boolean;
  }>;
  primary_image_url?: string;
  created_at: string;
  updated_at: string;
}

interface Accommodation {
  id: number;
  name: string;
  type: string;
  city: string;
  address: string;
  status: string;
  compliance_status?: 'conforme' | 'non_conforme';
  price_per_night: number;
  host?: {
    id: number;
    name: string;
    email: string;
  };
}

export default function AdminAccommodationDetailPage() {
  const router = useRouter();
  const params = useParams();
  const accommodationId = params.id as string;
  const { user, isAuthenticated, isLoading } = useAuthStore();
  
  const [accommodation, setAccommodation] = useState<Accommodation | null>(null);
  const [rooms, setRooms] = useState<Room[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionLoading, setActionLoading] = useState<number | null>(null);
  const confirmAction = useConfirm();

  useEffect(() => {
    if (!isLoading && isAuthenticated && user) {
      if (!isAdmin(user)) {
        router.push('/dashboard/admin');
        return;
      }
    }
  }, [isAuthenticated, isLoading, user, router]);

  useEffect(() => {
    if (isAuthenticated && user && isAdmin(user)) {
      fetchData();
    }
  }, [accommodationId, isAuthenticated, user]);

  const fetchData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      // Charger l'établissement
      const accResponse = await api.get(`/admin/accommodations/${accommodationId}`);
      setAccommodation(accResponse.data?.data || accResponse.data);
      
      // Charger les chambres (admin voit toutes, actives et inactives)
      const roomsResponse = await api.get(`/admin/accommodations/${accommodationId}/rooms`);
      setRooms(roomsResponse.data);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erreur lors du chargement');
      console.error('Error fetching data:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleToggleStatus = async (roomId: number) => {
    const ok = await confirmAction({
      title: 'Changer le statut de la chambre',
      message: 'Voulez-vous changer le statut de cette chambre ?',
      confirmLabel: 'Oui',
      cancelLabel: 'Annuler',
    });
    if (!ok) return;

    try {
      setActionLoading(roomId);
      await api.post(`/admin/accommodations/${accommodationId}/rooms/${roomId}/toggle-status`);
      await fetchData(); // Recharger les données
    } catch (err: any) {
      alert(err.response?.data?.message || 'Erreur lors de la modification');
    } finally {
      setActionLoading(null);
    }
  };

  const handleDelete = async (roomId: number) => {
    const ok = await confirmAction({
      title: 'Supprimer la chambre',
      message: 'Êtes-vous sûr de vouloir supprimer cette chambre ? Cette action est irréversible.',
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
      variant: 'danger',
    });
    if (!ok) return;

    try {
      setActionLoading(roomId);
      await api.delete(`/admin/accommodations/${accommodationId}/rooms/${roomId}`);
      await fetchData(); // Recharger les données
    } catch (err: any) {
      alert(err.response?.data?.message || 'Erreur lors de la suppression');
    } finally {
      setActionLoading(null);
    }
  };

  if (isLoading || loading) {
    return (
      <div className="min-h-screen">
        <Header />
        <div className="container mx-auto px-4 py-8">
          <LoadingSpinner size="lg" />
        </div>
        <Footer />
      </div>
    );
  }

  if (!isAuthenticated || !user || !isAdmin(user)) {
    return null;
  }

  if (error || !accommodation) {
    return (
      <div className="min-h-screen">
        <Header />
        <div className="container mx-auto px-4 py-12">
          <ErrorDisplay error={error || 'Établissement introuvable'} />
        </div>
        <Footer />
      </div>
    );
  }

  const activeRooms = rooms.filter(r => r.is_active);
  const inactiveRooms = rooms.filter(r => !r.is_active);

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <Header />
      
      <main className="container mx-auto px-4 py-8">
        {/* Breadcrumb */}
        <div className="mb-6">
          <Link
            href="/dashboard/admin/accommodations"
            className="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            Retour aux établissements
          </Link>
        </div>

        {/* En-tête */}
        <div className="card mb-6">
          <div className="flex items-start justify-between mb-4">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                {accommodation.name}
              </h1>
              <div className="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                <span className="flex items-center gap-1">
                  <MapPin className="w-4 h-4" />
                  {accommodation.city}
                </span>
                <span className="flex items-center gap-1">
                  <Building2 className="w-4 h-4" />
                  {accommodation.type}
                </span>
                {accommodation.host && (
                  <span className="flex items-center gap-1">
                    <Users className="w-4 h-4" />
                    {accommodation.host.name}
                  </span>
                )}
                <span
                  className={`px-2 py-1 text-xs font-medium rounded-full ${
                    accommodation.compliance_status === 'conforme'
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                  }`}
                >
                  {accommodation.compliance_status === 'conforme' ? 'Conforme' : 'Non conforme'}
                </span>
              </div>
            </div>
            <Link
              href={`/accommodations/${accommodation.id}`}
              target="_blank"
              rel="noopener noreferrer"
              className="btn-secondary"
            >
              <Eye className="w-4 h-4 inline mr-1" />
              Voir l'établissement
            </Link>
          </div>
        </div>

        {/* Statistiques */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div className="card">
            <div className="flex items-center gap-3">
              <Bed className="w-8 h-8 text-primary" />
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-400">Total chambres</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{rooms.length}</p>
              </div>
            </div>
          </div>
          <div className="card">
            <div className="flex items-center gap-3">
              <CheckCircle className="w-8 h-8 text-green-500" />
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-400">Actives</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{activeRooms.length}</p>
              </div>
            </div>
          </div>
          <div className="card">
            <div className="flex items-center gap-3">
              <XCircle className="w-8 h-8 text-red-500" />
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-400">Inactives</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{inactiveRooms.length}</p>
              </div>
            </div>
          </div>
          <div className="card">
            <div className="flex items-center gap-3">
              <DollarSign className="w-8 h-8 text-yellow-500" />
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-400">Prix moyen</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                  {rooms.length > 0
                    ? formatPrice(
                        rooms.reduce((sum, r) => sum + r.price_per_night, 0) / rooms.length
                      )
                    : '0'}
                  {' '}FCFA
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Liste des chambres */}
        <div className="card">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
              Chambres ({rooms.length})
            </h2>
          </div>

          {rooms.length === 0 ? (
            <div className="text-center py-12 text-gray-500 dark:text-gray-400">
              Aucune chambre pour cet établissement
            </div>
          ) : (
            <div className="space-y-4">
              {rooms.map((room) => {
                const primaryImage = room.images?.find(img => img.is_primary) || room.images?.[0];
                const imageUrl = primaryImage?.full_url || room.primary_image_url;

                return (
                  <div
                    key={room.id}
                    className={`border rounded-lg p-4 ${
                      room.is_active
                        ? 'border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10'
                        : 'border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10'
                    }`}
                  >
                    <div className="flex gap-4">
                      {/* Image */}
                      {imageUrl && (
                        <div className="relative w-32 h-32 rounded-lg overflow-hidden flex-shrink-0">
                          <Image
                            src={imageUrl}
                            alt={room.name}
                            fill
                            className="object-cover"
                          />
                        </div>
                      )}

                      {/* Détails */}
                      <div className="flex-grow">
                        <div className="flex items-start justify-between mb-2">
                          <div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                              {room.name}
                            </h3>
                            {room.room_category && (
                              <p className="text-sm text-gray-600 dark:text-gray-400">
                                {room.room_category}
                                {room.room_subcategory && ` • ${room.room_subcategory}`}
                              </p>
                            )}
                          </div>
                          <div className="flex items-center gap-2">
                            {room.is_active ? (
                              <span className="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                Active
                              </span>
                            ) : (
                              <span className="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                Inactive
                              </span>
                            )}
                          </div>
                        </div>

                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3 text-sm">
                          <div>
                            <p className="text-gray-600 dark:text-gray-400">Capacité</p>
                            <p className="font-semibold text-gray-900 dark:text-white">
                              {room.capacity} personne{room.capacity > 1 ? 's' : ''}
                            </p>
                          </div>
                          <div>
                            <p className="text-gray-600 dark:text-gray-400">Prix/nuit</p>
                            <p className="font-semibold text-gray-900 dark:text-white">
                              {formatPrice(room.price_per_night)} FCFA
                            </p>
                          </div>
                          <div>
                            <p className="text-gray-600 dark:text-gray-400">Quantité</p>
                            <p className="font-semibold text-gray-900 dark:text-white">
                              {room.quantity || 1} chambre{room.quantity && room.quantity > 1 ? 's' : ''}
                            </p>
                          </div>
                          {room.surface_area && (
                            <div>
                              <p className="text-gray-600 dark:text-gray-400">Superficie</p>
                              <p className="font-semibold text-gray-900 dark:text-white">
                                {room.surface_area} m²
                              </p>
                            </div>
                          )}
                        </div>

                        {room.description && (
                          <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                            {room.description}
                          </p>
                        )}

                        {/* Actions */}
                        <div className="flex gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                          <button
                            onClick={() => handleToggleStatus(room.id)}
                            disabled={actionLoading === room.id}
                            className={`px-3 py-1.5 rounded-lg text-sm transition-colors ${
                              room.is_active
                                ? 'bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                : 'bg-green-100 hover:bg-green-200 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                            } disabled:opacity-50`}
                          >
                            {actionLoading === room.id ? (
                              '...'
                            ) : room.is_active ? (
                              <>
                                <EyeOff className="w-4 h-4 inline mr-1" />
                                Désactiver
                              </>
                            ) : (
                              <>
                                <Eye className="w-4 h-4 inline mr-1" />
                                Activer
                              </>
                            )}
                          </button>
                          <button
                            onClick={() => handleDelete(room.id)}
                            disabled={actionLoading === room.id}
                            className="px-3 py-1.5 rounded-lg text-sm bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/30 dark:text-red-400 transition-colors disabled:opacity-50"
                          >
                            <Trash2 className="w-4 h-4 inline mr-1" />
                            Supprimer
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </main>

      <Footer />
    </div>
  );
}
