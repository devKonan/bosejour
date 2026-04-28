'use client';

import { useState } from 'react';
import api from '@/lib/api';
import { KeyRound, LogIn } from 'lucide-react';

interface HostCheckInCardProps {
  className?: string;
}

export default function HostCheckInCard({ className = '' }: HostCheckInCardProps) {
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const trimmed = code.trim().toUpperCase();
    if (!trimmed) return;
    setLoading(true);
    setMessage(null);
    try {
      await api.post('/host/check-in', { confirmation_code: trimmed });
      setMessage({ type: 'success', text: 'Arrivée enregistrée. Le séjour a démarré.' });
      setCode('');
    } catch (err: any) {
      setMessage({
        type: 'error',
        text: err.response?.data?.message ?? 'Code invalide ou réservation déjà enregistrée.',
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className={`p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 ${className}`}>
      <h3 className="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
        <LogIn className="w-5 h-5 text-primary" />
        Enregistrer une arrivée
      </h3>
      <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
        Le client vous communique son code réservation : saisissez-le pour marquer le début du séjour et débloquer le reversement.
      </p>
      <form onSubmit={handleSubmit} className="flex flex-wrap items-end gap-2">
        <div className="flex-1 min-w-[140px]">
          <label htmlFor="checkin-code" className="sr-only">Code réservation</label>
          <input
            id="checkin-code"
            type="text"
            value={code}
            onChange={(e) => setCode(e.target.value.toUpperCase().slice(0, 8))}
            placeholder="Ex: A1B2C3D4"
            maxLength={8}
            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 font-mono uppercase"
          />
        </div>
        <button
          type="submit"
          disabled={loading || !code.trim()}
          className="btn-primary inline-flex items-center gap-2 disabled:opacity-50"
        >
          <KeyRound className="w-4 h-4" />
          {loading ? 'Enregistrement...' : 'Valider l\'arrivée'}
        </button>
      </form>
      {message && (
        <p className={`mt-2 text-sm ${message.type === 'success' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`}>
          {message.text}
        </p>
      )}
    </div>
  );
}
