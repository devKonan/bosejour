'use client';

import { useState } from 'react';
import Image from 'next/image';
import { formatPrice } from '@/lib/utils';
import { Users, Bed, Bath, ChevronLeft, ChevronRight } from 'lucide-react';

interface RoomImage {
  id: number;
  full_url: string;
  thumbnail_url: string;
  caption?: string;
  is_primary: boolean;
}

interface Room {
  id: number;
  name: string;
  type: string;
  description?: string;
  capacity: number;
  price_per_night: number;
  bedrooms: number;
  bathrooms: number;
  amenities?: string[];
  images?: RoomImage[];
  primaryImage?: RoomImage;
}

interface RoomCardProps {
  room: Room;
  onSelect?: (room: Room) => void;
  selected?: boolean;
  showDetails?: boolean;
}

export default function RoomCard({ room, onSelect, selected, showDetails = true }: RoomCardProps) {
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const images = room.images || [];
  const hasImages = images.length > 0;

  const nextImage = (e: React.MouseEvent) => {
    e.stopPropagation();
    setCurrentImageIndex((prev) => (prev + 1) % images.length);
  };

  const prevImage = (e: React.MouseEvent) => {
    e.stopPropagation();
    setCurrentImageIndex((prev) => (prev - 1 + images.length) % images.length);
  };

  const currentImage = hasImages ? images[currentImageIndex] : null;

  return (
    <div
      className={`card overflow-hidden cursor-pointer transition-all hover:shadow-lg ${
        selected ? 'ring-2 ring-primary' : ''
      }`}
      onClick={() => onSelect?.(room)}
    >
      {/* Image carousel */}
      <div className="relative h-48 sm:h-56 bg-gray-200 dark:bg-gray-700">
        {currentImage ? (
          <>
            <Image
              src={currentImage.full_url}
              alt={currentImage.caption || room.name}
              fill
              className="object-cover"
              sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
            />
            {images.length > 1 && (
              <>
                <button
                  onClick={prevImage}
                  className="absolute left-2 top-1/2 -translate-y-1/2 bg-white/80 dark:bg-gray-800/80 p-2 rounded-full hover:bg-white dark:hover:bg-gray-800 transition-colors"
                  aria-label="Image précédente"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                <button
                  onClick={nextImage}
                  className="absolute right-2 top-1/2 -translate-y-1/2 bg-white/80 dark:bg-gray-800/80 p-2 rounded-full hover:bg-white dark:hover:bg-gray-800 transition-colors"
                  aria-label="Image suivante"
                >
                  <ChevronRight className="w-5 h-5" />
                </button>
                <div className="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1">
                  {images.map((_, index) => (
                    <div
                      key={index}
                      className={`w-2 h-2 rounded-full ${
                        index === currentImageIndex
                          ? 'bg-white'
                          : 'bg-white/50'
                      }`}
                    />
                  ))}
                </div>
              </>
            )}
          </>
        ) : (
          <div className="flex items-center justify-center h-full text-gray-400">
            <Bed className="w-16 h-16" />
          </div>
        )}
      </div>

      {/* Room info */}
      <div className="p-4">
        <div className="flex justify-between items-start mb-2">
          <div>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
              {room.name}
            </h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">{room.type}</p>
          </div>
          <div className="text-right">
            <p className="text-xl font-bold text-primary">
              {formatPrice(room.price_per_night)} FCFA
            </p>
            <p className="text-xs text-gray-600 dark:text-gray-400">par nuit</p>
          </div>
        </div>

        {showDetails && room.description && (
          <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
            {room.description}
          </p>
        )}

        {/* Amenities */}
        <div className="flex gap-4 text-sm text-gray-600 dark:text-gray-400">
          <div className="flex items-center gap-1">
            <Users className="w-4 h-4" />
            <span>{room.capacity} pers.</span>
          </div>
          <div className="flex items-center gap-1">
            <Bed className="w-4 h-4" />
            <span>{room.bedrooms} ch.</span>
          </div>
          <div className="flex items-center gap-1">
            <Bath className="w-4 h-4" />
            <span>{room.bathrooms} sdb</span>
          </div>
        </div>

        {room.amenities && room.amenities.length > 0 && showDetails && (
          <div className="mt-3 flex flex-wrap gap-2">
            {room.amenities.slice(0, 3).map((amenity, index) => (
              <span
                key={index}
                className="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-full"
              >
                {amenity}
              </span>
            ))}
            {room.amenities.length > 3 && (
              <span className="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-full">
                +{room.amenities.length - 3}
              </span>
            )}
          </div>
        )}

        {onSelect && (
          <button
            className={`w-full mt-4 py-2 px-4 rounded-lg font-medium transition-colors ${
              selected
                ? 'bg-primary text-white'
                : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600'
            }`}
          >
            {selected ? 'Chambre sélectionnée' : 'Sélectionner'}
          </button>
        )}
      </div>
    </div>
  );
}
