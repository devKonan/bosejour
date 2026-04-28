'use client';

import { useBookingHistory } from '@/hooks/useBookings';
import { BOOKING_STATUS_CONFIG } from '@/types/booking';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { Clock, User, ArrowRight } from 'lucide-react';

interface Props {
  bookingId: number;
}

const ACTION_LABELS: Record<string, string> = {
  created:   'Réservation créée',
  confirmed: 'Réservation confirmée',
  cancelled: 'Réservation annulée',
  completed: 'Séjour terminé',
  modified:  'Réservation modifiée',
};

export default function BookingHistory({ bookingId }: Props) {
  const { data: history, isLoading } = useBookingHistory(bookingId);

  if (isLoading) {
    return (
      <div className="space-y-3 animate-pulse">
        {[1, 2, 3].map((i) => (
          <div key={i} className="h-12 bg-gray-100 dark:bg-gray-800 rounded-lg" />
        ))}
      </div>
    );
  }

  if (!history?.length) return null;

  return (
    <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
      <h3 className="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <Clock className="w-4 h-4" />
        Historique
      </h3>

      <ol className="relative border-l border-gray-200 dark:border-gray-700 space-y-4 ml-2">
        {history.map((entry) => {
          const toConfig = BOOKING_STATUS_CONFIG[entry.to_status];
          const fromConfig = entry.from_status ? BOOKING_STATUS_CONFIG[entry.from_status] : null;

          return (
            <li key={entry.id} className="ml-4">
              {/* Point sur la timeline */}
              <span className="absolute -left-1.5 mt-1 w-3 h-3 rounded-full border-2 border-white dark:border-gray-800 bg-gray-400 dark:bg-gray-500" />

              <div className="flex flex-col gap-0.5">
                <div className="flex items-center gap-2 flex-wrap">
                  <span className="text-sm font-medium text-gray-900 dark:text-white">
                    {ACTION_LABELS[entry.action] ?? entry.action}
                  </span>

                  {/* Transition de statut */}
                  {fromConfig && (
                    <span className="flex items-center gap-1 text-xs">
                      <span className={`px-2 py-0.5 rounded-full ${fromConfig.bg} ${fromConfig.color}`}>
                        {fromConfig.label}
                      </span>
                      <ArrowRight className="w-3 h-3 text-gray-400" />
                      <span className={`px-2 py-0.5 rounded-full ${toConfig.bg} ${toConfig.color}`}>
                        {toConfig.label}
                      </span>
                    </span>
                  )}
                </div>

                {/* Métadonnées (modification dates) */}
                {entry.meta && (entry.meta as any).old_check_in && (
                  <p className="text-xs text-gray-500 dark:text-gray-400">
                    {(entry.meta as any).old_check_in} → {(entry.meta as any).new_check_in}{' '}
                    / {(entry.meta as any).old_check_out} → {(entry.meta as any).new_check_out}
                  </p>
                )}

                {entry.note && (
                  <p className="text-xs text-gray-500 dark:text-gray-400 italic">
                    &quot;{entry.note}&quot;
                  </p>
                )}

                <div className="flex items-center gap-3 text-xs text-gray-400">
                  <span>{format(new Date(entry.created_at), 'dd MMM yyyy HH:mm', { locale: fr })}</span>
                  {entry.user && (
                    <span className="flex items-center gap-1">
                      <User className="w-3 h-3" />
                      {entry.user.name}
                    </span>
                  )}
                </div>
              </div>
            </li>
          );
        })}
      </ol>
    </div>
  );
}
