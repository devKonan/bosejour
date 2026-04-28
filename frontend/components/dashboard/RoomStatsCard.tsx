'use client';

import { Bed, Image as ImageIcon, Eye, EyeOff, AlertTriangle, TrendingUp } from 'lucide-react';

interface RoomStats {
  total_rooms: number;
  active_rooms: number;
  inactive_rooms: number;
  rooms_needing_images: number;
  avg_images_per_room: number;
  room_bookings_last_30_days?: number;
  total_room_images?: number;
}

interface RoomStatsCardProps {
  stats: RoomStats;
  isAdmin?: boolean;
}

export default function RoomStatsCard({ stats, isAdmin = false }: RoomStatsCardProps) {
  const activationRate = stats.total_rooms > 0 
    ? (stats.active_rooms / stats.total_rooms) * 100 
    : 0;

  return (
    <div className="card">
      <div className="flex items-center justify-between mb-6">
        <h3 className="text-xl font-semibold flex items-center gap-2">
          <Bed className="w-6 h-6 text-primary" />
          Statistiques des chambres
        </h3>
        {stats.rooms_needing_images > 0 && (
          <span className="bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-400 px-3 py-1 rounded-full text-sm flex items-center gap-1">
            <AlertTriangle className="w-4 h-4" />
            {stats.rooms_needing_images} nécessite(nt) des images
          </span>
        )}
      </div>

      {/* Stats principales */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
          <div className="flex items-center gap-2 mb-2">
            <Bed className="w-5 h-5 text-gray-600 dark:text-gray-400" />
            <p className="text-sm text-gray-600 dark:text-gray-400">Total</p>
          </div>
          <p className="text-2xl font-bold">{stats.total_rooms}</p>
        </div>

        <div className="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
          <div className="flex items-center gap-2 mb-2">
            <Eye className="w-5 h-5 text-green-600 dark:text-green-400" />
            <p className="text-sm text-green-600 dark:text-green-400">Actives</p>
          </div>
          <p className="text-2xl font-bold text-green-600 dark:text-green-400">
            {stats.active_rooms}
          </p>
        </div>

        <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
          <div className="flex items-center gap-2 mb-2">
            <EyeOff className="w-5 h-5 text-gray-600 dark:text-gray-400" />
            <p className="text-sm text-gray-600 dark:text-gray-400">Inactives</p>
          </div>
          <p className="text-2xl font-bold">{stats.inactive_rooms}</p>
        </div>

        <div className="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
          <div className="flex items-center gap-2 mb-2">
            <ImageIcon className="w-5 h-5 text-blue-600 dark:text-blue-400" />
            <p className="text-sm text-blue-600 dark:text-blue-400">Moy. images</p>
          </div>
          <p className="text-2xl font-bold text-blue-600 dark:text-blue-400">
            {stats.avg_images_per_room}
          </p>
        </div>
      </div>

      {/* Taux d'activation */}
      <div className="mb-6">
        <div className="flex justify-between items-center mb-2">
          <span className="text-sm font-medium">Taux d'activation</span>
          <span className="text-sm font-bold text-primary">{activationRate.toFixed(1)}%</span>
        </div>
        <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
          <div
            className="bg-primary rounded-full h-2 transition-all duration-300"
            style={{ width: `${activationRate}%` }}
          />
        </div>
      </div>

      {/* Stats supplémentaires */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {stats.rooms_needing_images > 0 && (
          <div className="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
            <div className="flex items-center gap-2 mb-1">
              <AlertTriangle className="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
              <p className="text-sm font-medium text-yellow-800 dark:text-yellow-400">
                Chambres à compléter
              </p>
            </div>
            <p className="text-2xl font-bold text-yellow-800 dark:text-yellow-400">
              {stats.rooms_needing_images}
            </p>
            <p className="text-xs text-yellow-700 dark:text-yellow-500 mt-1">
              Moins de 3 images
            </p>
          </div>
        )}

        {!isAdmin && stats.room_bookings_last_30_days !== undefined && (
          <div className="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
            <div className="flex items-center gap-2 mb-1">
              <TrendingUp className="w-4 h-4 text-purple-600 dark:text-purple-400" />
              <p className="text-sm font-medium text-purple-800 dark:text-purple-400">
                Réservations (30j)
              </p>
            </div>
            <p className="text-2xl font-bold text-purple-800 dark:text-purple-400">
              {stats.room_bookings_last_30_days}
            </p>
            <p className="text-xs text-purple-700 dark:text-purple-500 mt-1">
              Chambres réservées
            </p>
          </div>
        )}

        {isAdmin && stats.total_room_images !== undefined && (
          <div className="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg border border-indigo-200 dark:border-indigo-800">
            <div className="flex items-center gap-2 mb-1">
              <ImageIcon className="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
              <p className="text-sm font-medium text-indigo-800 dark:text-indigo-400">
                Total images
              </p>
            </div>
            <p className="text-2xl font-bold text-indigo-800 dark:text-indigo-400">
              {stats.total_room_images}
            </p>
            <p className="text-xs text-indigo-700 dark:text-indigo-500 mt-1">
              Images uploadées
            </p>
          </div>
        )}
      </div>

      {/* Message d'encouragement */}
      {stats.rooms_needing_images > 0 && !isAdmin && (
        <div className="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
          <p className="text-sm text-blue-800 dark:text-blue-400">
            💡 <strong>Conseil :</strong> Ajoutez au moins 3 images de qualité à vos chambres 
            pour les activer et augmenter vos réservations.
          </p>
        </div>
      )}
    </div>
  );
}
