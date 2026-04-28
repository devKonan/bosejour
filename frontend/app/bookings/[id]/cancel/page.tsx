'use client';

import { useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useBooking, useCancelBooking } from '@/hooks/useBookings';
import { BOOKING_STATUS_CONFIG } from '@/types/booking';
import { AlertTriangle, ArrowLeft, Loader2 } from 'lucide-react';
import Link from 'next/link';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import { formatPrice } from '@/lib/utils';
import Header from '@/components/common/Header';

const CANCEL_REASONS = [
  'Changement de plans',
  'Erreur de dates',
  'Problème personnel',
  'Trouvé une meilleure offre',
  'Autre',
];

export default function CancelBookingPage() {
  const params = useParams();
  const router = useRouter();
  const bookingId = Number(params.id);

  const { data: booking, isLoading } = useBooking(bookingId);
  const { mutateAsync: cancel, isPending } = useCancelBooking();

  const [selectedReason, setSelectedReason] = useState('');
  const [customReason, setCustomReason] = useState('');
  const [confirmed, setConfirmed] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const finalReason = selectedReason === 'Autre' ? customReason : selectedReason;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!confirmed) return;

    try {
      setError(null);
      await cancel({ id: bookingId, reason: finalReason });
      router.push(`/bookings/${bookingId}?cancelled=1`);
    } catch (err: any) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue. Veuillez réessayer.');
    }
  };

  if (isLoading) {
    return (
      <div className="min-h-screen">
        <Header />
        <div className="flex items-center justify-center py-20">
          <Loader2 className="w-6 h-6 animate-spin text-gray-400" />
        </div>
      </div>
    );
  }

  if (!booking) {
    return (
      <div className="min-h-screen">
        <Header />
        <div className="max-w-lg mx-auto px-4 py-12 text-center">
          <p className="text-gray-500">Réservation introuvable.</p>
          <Link href="/bookings" className="btn-primary mt-4 inline-block">
            Mes réservations
          </Link>
        </div>
      </div>
    );
  }

  if (booking.status === 'cancelled' || booking.status === 'completed') {
    const cfg = BOOKING_STATUS_CONFIG[booking.status];
    return (
      <div className="min-h-screen">
        <Header />
        <div className="max-w-lg mx-auto px-4 py-12 text-center">
          <span className={`px-3 py-1 rounded-full text-sm font-medium ${cfg.bg} ${cfg.color}`}>
            {cfg.label}
          </span>
          <p className="mt-4 text-gray-500">Cette réservation ne peut plus être annulée.</p>
          <Link href={`/bookings/${bookingId}`} className="btn-primary mt-4 inline-block">
            Retour
          </Link>
        </div>
      </div>
    );
  }

  const isNonRefundable = booking.is_non_refundable;
  const amountPaid = Number(booking.amount_paid ?? 0);
  const canModifyFree = booking.cancellation_policy_hours_snapshot
    ? new Date(booking.check_in) > new Date(Date.now() + booking.cancellation_policy_hours_snapshot * 3600_000)
    : true;

  return (
    <div className="min-h-screen">
      <Header />
      <main className="max-w-lg mx-auto px-4 py-8">
        <Link
          href={`/bookings/${bookingId}`}
          className="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 mb-6"
        >
          <ArrowLeft className="w-4 h-4" />
          Retour à la réservation
        </Link>

        <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">
          Annuler la réservation
        </h1>

        {/* Récapitulatif */}
        <div className="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 mb-6 space-y-2 text-sm">
          <p className="font-semibold text-gray-900 dark:text-white">
            {booking.accommodation?.name}
          </p>
          <p className="text-gray-600 dark:text-gray-400">
            {format(new Date(booking.check_in), 'dd MMM', { locale: fr })} →{' '}
            {format(new Date(booking.check_out), 'dd MMM yyyy', { locale: fr })}
          </p>
          <p className="text-gray-600 dark:text-gray-400">
            Total : {formatPrice(booking.total_price)} FCFA
          </p>
        </div>

        {/* Politique d'annulation */}
        <div className={`rounded-xl p-4 mb-6 flex gap-3 ${
          isNonRefundable
            ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800'
            : 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800'
        }`}>
          <AlertTriangle className="w-5 h-5 mt-0.5 flex-shrink-0" />
          <div className="text-sm space-y-1">
            {isNonRefundable ? (
              <>
                <p className="font-semibold">Réservation non remboursable</p>
                {amountPaid > 0 && (
                  <p>Les {formatPrice(amountPaid)} FCFA déjà payés ne seront pas remboursés.</p>
                )}
              </>
            ) : canModifyFree ? (
              <>
                <p className="font-semibold">Annulation gratuite</p>
                {amountPaid > 0 && (
                  <p>
                    Un avoir de {formatPrice(amountPaid)} FCFA sera crédité sur votre compte
                    pour une prochaine réservation.
                  </p>
                )}
              </>
            ) : (
              <>
                <p className="font-semibold">Délai d&apos;annulation dépassé</p>
                <p>L&apos;annulation gratuite n&apos;est plus possible dans ce délai.</p>
                {amountPaid > 0 && (
                  <p>Un avoir de {formatPrice(amountPaid)} FCFA sera crédité si applicable.</p>
                )}
              </>
            )}
          </div>
        </div>

        {/* Formulaire */}
        <form onSubmit={handleSubmit} className="space-y-5">
          <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Motif d&apos;annulation
            </label>
            <div className="space-y-2">
              {CANCEL_REASONS.map((r) => (
                <label key={r} className="flex items-center gap-3 cursor-pointer">
                  <input
                    type="radio"
                    name="reason"
                    value={r}
                    checked={selectedReason === r}
                    onChange={() => setSelectedReason(r)}
                    className="accent-primary"
                  />
                  <span className="text-sm text-gray-700 dark:text-gray-300">{r}</span>
                </label>
              ))}
            </div>
          </div>

          {selectedReason === 'Autre' && (
            <div>
              <textarea
                value={customReason}
                onChange={(e) => setCustomReason(e.target.value)}
                placeholder="Décrivez votre motif..."
                rows={3}
                maxLength={500}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-900 focus:ring-2 focus:ring-primary outline-none"
              />
            </div>
          )}

          <label className="flex items-start gap-3 cursor-pointer">
            <input
              type="checkbox"
              checked={confirmed}
              onChange={(e) => setConfirmed(e.target.checked)}
              className="mt-0.5 accent-primary"
            />
            <span className="text-sm text-gray-700 dark:text-gray-300">
              Je confirme vouloir annuler cette réservation et j&apos;ai compris la politique d&apos;annulation.
            </span>
          </label>

          {error && (
            <p className="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-3 py-2 rounded-lg">
              {error}
            </p>
          )}

          <button
            type="submit"
            disabled={!confirmed || !selectedReason || isPending}
            className="w-full py-3 rounded-xl font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
          >
            {isPending ? (
              <>
                <Loader2 className="w-4 h-4 animate-spin" />
                Annulation en cours...
              </>
            ) : (
              'Confirmer l\'annulation'
            )}
          </button>
        </form>
      </main>
    </div>
  );
}
