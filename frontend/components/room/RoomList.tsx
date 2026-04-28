'use client';

import { useState, useEffect } from 'react';
import RoomCard from './RoomCard';
import LoadingSpinner from '@/components/common/LoadingSpinner';
import api from '@/lib/api';

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
  images?: any[];
  primaryImage?: any;
}

interface RoomListProps {
  accommodationId: number;
  onRoomSelect?: (room: Room | null) => void;
  selectedRoomId?: number | null;
  checkIn?: string;
  checkOut?: string;
  guests?: number;
}

export default function RoomList({
  accommodationId,
  onRoomSelect,
  selectedRoomId,
  checkIn,
  checkOut,
  guests,
}: RoomListProps) {
  const [rooms, setRooms] = useState<Room[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchRooms();
  }, [accommodationId]);

  const fetchRooms = async () => {
    try {
      setLoading(true);
      const response = await api.get(`/accommodations/${accommodationId}/rooms`);
      setRooms(response.data);
      setError(null);
    } catch (err: any) {
      console.error('Error fetching rooms:', err);
      setError('Erreur lors du chargement des chambres');
    } finally {
      setLoading(false);
    }
  };

  const handleRoomSelect = (room: Room) => {
    if (selectedRoomId === room.id) {
      onRoomSelect?.(null); // Désélectionner
    } else {
      onRoomSelect?.(room);
    }
  };

  // Filtrer les chambres selon la capacité si spécifiée
  const filteredRooms = guests
    ? rooms.filter((room) => room.capacity >= guests)
    : rooms;

  if (loading) {
    return (
      <div className="flex justify-center py-12">
        <LoadingSpinner />
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 p-4 rounded-lg">
        {error}
      </div>
    );
  }

  if (filteredRooms.length === 0) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-600 dark:text-gray-400">
          {guests && guests > 0
            ? `Aucune chambre disponible pour ${guests} personne(s)`
            : 'Aucune chambre disponible'}
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <h3 className="text-xl font-semibold mb-4">
        Chambres disponibles {filteredRooms.length > 0 && `(${filteredRooms.length})`}
      </h3>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {filteredRooms.map((room) => (
          <RoomCard
            key={room.id}
            room={room}
            onSelect={onRoomSelect ? handleRoomSelect : undefined}
            selected={selectedRoomId === room.id}
          />
        ))}
      </div>
    </div>
  );
}
