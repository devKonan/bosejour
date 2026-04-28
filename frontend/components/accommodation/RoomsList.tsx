'use client';

import Image from 'next/image';
import { formatPrice } from '@/lib/utils';
import { Bed, Users, Expand, Image as ImageIcon, Star, CheckCircle, XCircle, Calendar } from 'lucide-react';
import { useState } from 'react';
import { useSearchParams } from 'next/navigation';

interface RoomImage {
  id: number;
  image_path: string;
  full_url: string;
  thumbnail_url: string;
  is_primary: boolean;
  caption?: string;
}

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
  surface_area?: number;
  amenities?: string[];
  basic_amenities?: string[];
  is_active: boolean;
  is_available?: boolean; // Disponibilité pour les dates sélectionnées
  quantity?: number; // Nombre total de chambres de ce type
  available_quantity?: number; // Nombre de chambres disponibles pour les dates sélectionnées
  images?: RoomImage[];
  primary_image_url?: string;
  room_category?: string;
  room_subcategory?: string;
  view_type?: string;
}

interface RoomsListProps {
  rooms: Room[];
  onSelectRoom?: (room: Room) => void;
  checkIn?: Date | null;
  checkOut?: Date | null;
  guests?: number;
}

export default function RoomsList({ rooms, onSelectRoom, checkIn: propCheckIn, checkOut: propCheckOut, guests }: RoomsListProps) {
  const [selectedImageRoom, setSelectedImageRoom] = useState<number | null>(null);
  const searchParams = useSearchParams();
  const urlCheckIn = searchParams?.get('check_in');
  const urlCheckOut = searchParams?.get('check_out');
  
  // Utiliser les props en priorité, sinon les paramètres URL
  const checkIn = propCheckIn ? propCheckIn.toISOString().split('T')[0] : urlCheckIn;
  const checkOut = propCheckOut ? propCheckOut.toISOString().split('T')[0] : urlCheckOut;

  if (!rooms || rooms.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500">
        Aucune chambre disponible pour le moment.
      </div>
    );
  }

  const handleRoomClick = (room: Room) => {
    if (onSelectRoom) {
      onSelectRoom(room);
    } else {
      // Rediriger vers la page de détails de la chambre avec les dates et voyageurs
      const params = new URLSearchParams();
      if (checkIn) params.set('check_in', checkIn);
      if (checkOut) params.set('check_out', checkOut);
      if (guests != null && guests > 0) params.set('guests', String(guests));
      const queryString = params.toString();
      window.location.href = `/rooms/${room.id}${queryString ? `?${queryString}` : ''}`;
    }
  };

  const getCategoryLabel = (category?: string) => {
    const labels: Record<string, string> = {
      single: 'Single',
      double: 'Double',
      twin: 'Twin',
      triple: 'Triple',
      pmr: 'PMR',
      suite: 'Suite',
      other: 'Autre',
    };
    return category ? labels[category] || category : '';
  };

  const getViewLabel = (view?: string) => {
    const labels: Record<string, string> = {
      city: 'Vue ville',
      garden: 'Vue jardin',
      pool: 'Vue piscine',
      sea: 'Vue mer',
      mountain: 'Vue montagne',
      parking: 'Vue parking',
    };
    return view ? labels[view] || '' : '';
  };

  return (
      <div className="space-y-6">
        {checkIn && checkOut && (
          <div className="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p className="text-sm text-blue-800 dark:text-blue-300 flex items-center gap-2">
              <Calendar className="w-4 h-4" />
              <span>
                Disponibilité vérifiée pour : <strong>{new Date(checkIn).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}</strong> - <strong>{new Date(checkOut).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}</strong>
              </span>
            </p>
          </div>
        )}
        
        {!checkIn && !checkOut && (
          <div className="mb-4 p-3 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg">
            <p className="text-sm text-gray-600 dark:text-gray-400">
              💡 <strong>Astuce :</strong> Sélectionnez vos dates ci-dessus pour vérifier la disponibilité et les prix exacts pour votre séjour.
            </p>
          </div>
        )}

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {rooms.map((room) => {
          const primaryImage = room.images?.find(img => img.is_primary);
          const imageUrl = primaryImage?.full_url || room.primary_image_url || room.images?.[0]?.full_url;

          // Si pas de dates sélectionnées, toutes les chambres sont considérées comme "à vérifier"
          const isAvailable = checkIn && checkOut 
            ? (room.is_available !== false) 
            : true; // Sans dates, on affiche toutes les chambres mais on indique qu'il faut vérifier

          return (
            <div
              key={room.id}
              className={`card hover:shadow-lg transition-all cursor-pointer group relative ${
                !isAvailable ? 'opacity-75' : ''
              }`}
              onClick={() => isAvailable && handleRoomClick(room)}
            >
              {/* Badge disponibilité */}
              {checkIn && checkOut && room.is_available !== undefined && (
                <div className={`absolute top-3 right-3 z-10 px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1 ${
                  isAvailable 
                    ? 'bg-green-500 text-white' 
                    : 'bg-red-500 text-white'
                }`}>
                  {isAvailable ? (
                    <>
                      <CheckCircle className="w-3 h-3" />
                      {room.quantity && room.quantity > 1 && room.available_quantity !== undefined
                        ? `${room.available_quantity}/${room.quantity} disponibles`
                        : 'Disponible'}
                    </>
                  ) : (
                    <>
                      <XCircle className="w-3 h-3" />
                      {room.quantity && room.quantity > 1 ? 'Complet' : 'Occupée'}
                    </>
                  )}
                </div>
              )}
              
              {!checkIn && !checkOut && (
                <div className="absolute top-3 right-3 z-10 px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1 bg-blue-500 text-white">
                  <Calendar className="w-3 h-3" />
                  Vérifier disponibilité
                </div>
              )}
              
              {/* Badge quantité si plusieurs chambres */}
              {room.quantity && room.quantity > 1 && !checkIn && !checkOut && (
                <div className="absolute top-12 right-3 z-10 px-2 py-1 rounded-full text-xs font-medium bg-gray-800/70 text-white">
                  {room.quantity} chambres
                </div>
              )}

              {/* Image */}
              <div className="relative h-48 bg-gray-200 dark:bg-gray-700 rounded-t-lg overflow-hidden">
                {imageUrl ? (
                  <>
                    <Image
                      src={imageUrl}
                      alt={room.name}
                      fill
                      className={`object-cover transition-transform duration-300 ${
                        isAvailable ? 'group-hover:scale-105' : 'grayscale'
                      }`}
                    />
                    {room.images && room.images.length > 1 && (
                      <div className="absolute bottom-2 right-2 bg-black/70 text-white px-2 py-1 rounded-full text-xs flex items-center gap-1">
                        <ImageIcon className="w-3 h-3" />
                        {room.images.length}
                      </div>
                    )}
                  </>
                ) : (
                  <div className="w-full h-full flex items-center justify-center">
                    <Bed className="w-12 h-12 text-gray-400" />
                  </div>
                )}
              </div>

              {/* Détails */}
              <div className="p-4">
                <div className="mb-3">
                  <h3 className="text-lg font-semibold mb-1 group-hover:text-primary transition-colors">
                    {room.name}
                  </h3>
                  <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    {room.room_category && (
                      <span className="bg-primary/10 text-primary px-2 py-0.5 rounded">
                        {getCategoryLabel(room.room_category)}
                      </span>
                    )}
                    {room.view_type && (
                      <span className="text-xs">• {getViewLabel(room.view_type)}</span>
                    )}
                  </div>
                </div>

                {room.description && (
                  <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                    {room.description}
                  </p>
                )}

                {/* Caractéristiques */}
                <div className="flex items-center gap-4 text-sm mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                  <div className="flex items-center gap-1">
                    <Users className="w-4 h-4 text-gray-500" />
                    <span>{room.capacity} pers.</span>
                  </div>
                  {room.surface_area && (
                    <div className="flex items-center gap-1">
                      <Expand className="w-4 h-4 text-gray-500" />
                      <span>{room.surface_area} m²</span>
                    </div>
                  )}
                  <div className="flex items-center gap-1">
                    <Bed className="w-4 h-4 text-gray-500" />
                    <span>{room.bedrooms}</span>
                  </div>
                </div>

                {/* Prix et disponibilité */}
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-2xl font-bold text-primary">
                      {formatPrice(room.price_per_night)} FCFA
                    </p>
                    <p className="text-xs text-gray-500">par nuit</p>
                    {checkIn && checkOut && room.quantity && room.quantity > 1 && room.available_quantity !== undefined && (
                      <p className="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        {room.available_quantity} disponible{room.available_quantity > 1 ? 's' : ''} sur {room.quantity}
                      </p>
                    )}
                  </div>
                  <button 
                    className={`text-sm ${
                      isAvailable 
                        ? 'btn-primary' 
                        : 'bg-gray-300 dark:bg-gray-600 text-gray-500 cursor-not-allowed'
                    }`}
                    onClick={(e) => {
                      e.stopPropagation();
                      if (isAvailable) {
                        handleRoomClick(room);
                      }
                    }}
                    disabled={!isAvailable}
                  >
                    {isAvailable ? 'Voir détails' : 'Indisponible'}
                  </button>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
