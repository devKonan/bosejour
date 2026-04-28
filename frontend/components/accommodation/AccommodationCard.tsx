'use client';

import Link from 'next/link';
import { Star, MapPin } from 'lucide-react';
import { useSearchStore } from '@/stores/searchStore';
import { motion } from 'framer-motion';
import { formatPrice } from '@/lib/utils';
import ImageCarousel from './ImageCarousel';

interface AccommodationCardProps {
  accommodation: {
    id: number;
    name: string;
    slug: string;
    type: string;
    city: string;
    price_per_night: number;
    rating?: number | null;
    total_reviews?: number | null;
    images: Array<{ url: string; is_primary: boolean }>;
  };
}

export default function AccommodationCard({ accommodation }: AccommodationCardProps) {
  const { session } = useSearchStore();
  const queryParams = new URLSearchParams();
  if (session?.checkIn) queryParams.set('check_in', session.checkIn);
  if (session?.checkOut) queryParams.set('check_out', session.checkOut);
  if (session?.guests && session.guests > 0) queryParams.set('guests', String(session.guests));
  const href = queryParams.toString()
    ? `/accommodations/${accommodation.id}?${queryParams.toString()}`
    : `/accommodations/${accommodation.id}`;

  const images = accommodation.images && accommodation.images.length > 0
    ? accommodation.images
    : [{ url: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800', is_primary: true }];

  const typeLabels: Record<string, string> = {
    hotel: 'Hôtel',
    lodge: 'Lodge',
    guesthouse: 'Maison d\'hôtes',
    apartment: 'Appartement',
  };

  return (
    <Link href={href}>
      <motion.div 
        className="card hover:shadow-xl cursor-pointer h-full flex flex-col"
        whileHover={{ y: -8, scale: 1.02 }}
        transition={{ duration: 0.3, ease: 'easeOut' }}
      >
        <motion.div 
          className="relative h-48 mb-4 rounded-lg overflow-hidden flex-shrink-0"
          whileHover={{ scale: 1.05 }}
          transition={{ duration: 0.4, ease: 'easeOut' }}
        >
          <ImageCarousel images={images} className="h-full" />
        </motion.div>
        
        <div className="space-y-2 flex-1 flex flex-col">
          <div className="flex items-start justify-between gap-2">
            <h3 className="font-semibold text-lg line-clamp-2 flex-1">{accommodation.name}</h3>
            <span className="text-xs bg-primary/10 text-primary px-2 py-1 rounded flex-shrink-0">
              {typeLabels[accommodation.type] || accommodation.type}
            </span>
          </div>

          <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
            <MapPin className="w-4 h-4 mr-1 flex-shrink-0" />
            <span className="truncate">{accommodation.city}</span>
          </div>

          {accommodation.rating && typeof accommodation.rating === 'number' && accommodation.rating > 0 && (
            <div className="flex items-center space-x-1">
              <Star className="w-4 h-4 fill-accent text-accent flex-shrink-0" />
              <span className="font-semibold">{Number(accommodation.rating).toFixed(1)}</span>
              <span className="text-sm text-gray-600 dark:text-gray-400">
                ({accommodation.total_reviews || 0} avis)
              </span>
            </div>
          )}

          <div className="pt-2 border-t border-gray-200 dark:border-gray-700 mt-auto">
            <div className="flex items-center justify-between gap-2">
              <span className="text-xl xl:text-2xl font-bold text-primary truncate">
                {formatPrice(accommodation.price_per_night)} FCFA
              </span>
              <span className="text-sm text-gray-600 dark:text-gray-400 flex-shrink-0">/nuit</span>
            </div>
          </div>
        </div>
      </motion.div>
    </Link>
  );
}

