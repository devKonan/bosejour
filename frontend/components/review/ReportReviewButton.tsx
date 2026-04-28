'use client';

import { useState } from 'react';
import api from '@/lib/api';
import { Flag } from 'lucide-react';

interface ReportReviewButtonProps {
  reviewId: number;
}

export default function ReportReviewButton({ reviewId }: ReportReviewButtonProps) {
  const [open, setOpen] = useState(false);
  const [reason, setReason] = useState('');
  const [sending, setSending] = useState(false);
  const [sent, setSent] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSending(true);
    try {
      await api.post(`/reviews/${reviewId}/report`, { reason: reason.trim() || undefined });
      setSent(true);
      setOpen(false);
      setReason('');
    } finally {
      setSending(false);
    }
  };

  if (sent) {
    return (
      <p className="mt-2 text-xs text-amber-600 dark:text-amber-400">Avis signalé. Merci.</p>
    );
  }

  return (
    <div className="mt-2">
      {!open ? (
        <button
          type="button"
          onClick={() => setOpen(true)}
          className="text-xs text-gray-500 hover:text-amber-600 dark:hover:text-amber-400 inline-flex items-center gap-1"
        >
          <Flag className="w-3 h-3" />
          Signaler cet avis
        </button>
      ) : (
        <form onSubmit={handleSubmit} className="text-sm">
          <label className="block text-gray-600 dark:text-gray-400 mb-1">Motif (optionnel)</label>
          <textarea
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            placeholder="Pourquoi signalez-vous cet avis ?"
            rows={2}
            maxLength={500}
            className="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm"
          />
          <div className="flex gap-2 mt-1">
            <button type="submit" disabled={sending} className="text-xs btn-primary py-1 px-2 disabled:opacity-50">
              {sending ? 'Envoi...' : 'Envoyer le signalement'}
            </button>
            <button type="button" onClick={() => { setOpen(false); setReason(''); }} className="text-xs btn-secondary py-1 px-2">
              Annuler
            </button>
          </div>
        </form>
      )}
    </div>
  );
}
