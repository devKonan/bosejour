'use client';

import { useEffect, useState } from 'react';
import api from '@/lib/api';
import { MessageSquare, Send } from 'lucide-react';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

interface MessageItem {
  id: number;
  body: string;
  is_from_platform: boolean;
  created_at: string;
  sender_id: number | null;
  sender?: { id: number; name: string } | null;
  recipient?: { id: number; name: string } | null;
}

interface BookingMessageThreadProps {
  bookingId: number;
  currentUserId: number;
  accommodationName: string;
  isHost: boolean;
}

export default function BookingMessageThread({ bookingId, currentUserId, accommodationName, isHost }: BookingMessageThreadProps) {
  const [messages, setMessages] = useState<MessageItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [body, setBody] = useState('');
  const [sending, setSending] = useState(false);

  const fetchMessages = async () => {
    try {
      const res = await api.get(`/bookings/${bookingId}/messages`);
      setMessages(Array.isArray(res.data) ? res.data : []);
    } catch {
      setMessages([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMessages();
  }, [bookingId]);

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault();
    const text = body.trim();
    if (!text || sending) return;
    setSending(true);
    try {
      const res = await api.post(`/bookings/${bookingId}/messages`, { body: text });
      setMessages((prev) => [...prev, res.data]);
      setBody('');
    } finally {
      setSending(false);
    }
  };

  const isFromMe = (msg: MessageItem) => msg.sender_id === currentUserId;

  return (
    <div className="card">
      <h2 className="text-2xl font-bold mb-4 flex items-center gap-2">
        <MessageSquare className="w-6 h-6 text-primary" />
        Messages – {accommodationName}
      </h2>
      <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
        {isHost ? 'Échangez avec le voyageur' : "Échangez avec l'hôte"} concernant cette réservation.
      </p>

      {loading ? (
        <p className="text-gray-500 py-4">Chargement des messages...</p>
      ) : (
        <>
          <div className="space-y-3 max-h-64 overflow-y-auto mb-4 pr-2">
            {messages.length === 0 ? (
              <p className="text-gray-500 py-2">Aucun message. Envoyez le premier.</p>
            ) : (
              messages.map((msg) => (
                <div
                  key={msg.id}
                  className={`rounded-lg p-3 ${
                    msg.is_from_platform
                      ? 'bg-primary/10 border border-primary/20'
                      : isFromMe(msg)
                      ? 'bg-primary/10 ml-4 border border-primary/20'
                      : 'bg-gray-100 dark:bg-gray-800 mr-4'
                  }`}
                >
                  <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
                    {msg.is_from_platform ? (
                      <span>Plateforme Bosejour</span>
                    ) : (
                      <span>{msg.sender?.name ?? (isFromMe(msg) ? 'Vous' : 'Autre')}</span>
                    )}
                    <span>{format(new Date(msg.created_at), 'dd MMM HH:mm', { locale: fr })}</span>
                  </div>
                  <p className="text-gray-800 dark:text-gray-200 whitespace-pre-line text-sm">{msg.body}</p>
                </div>
              ))
            )}
          </div>
          <form onSubmit={handleSend} className="flex gap-2">
            <textarea
              value={body}
              onChange={(e) => setBody(e.target.value)}
              placeholder="Votre message..."
              rows={2}
              maxLength={2000}
              className="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm"
            />
            <button type="submit" disabled={sending || !body.trim()} className="btn-primary self-end flex items-center gap-2 disabled:opacity-50">
              <Send className="w-4 h-4" />
              Envoyer
            </button>
          </form>
        </>
      )}
    </div>
  );
}
