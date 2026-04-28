'use client';

import { useCallback, useEffect, useMemo, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { DayPicker } from 'react-day-picker';
import { fr as frLocale } from 'react-day-picker/locale';
import { addMonths, endOfMonth, format, startOfMonth } from 'date-fns';
import 'react-day-picker/style.css';
import Header from '@/components/common/Header';
import Footer from '@/components/common/Footer';
import LoadingSpinner from '@/components/common/LoadingSpinner';
import { useAuthStore } from '@/stores/authStore';
import api from '@/lib/api';
import Link from 'next/link';

type DayStatus = 'available' | 'occupied' | 'blocked' | 'maintenance';

interface CalendarDay {
  date: string;
  status: DayStatus;
  price_override: number | null;
}

interface Room {
  id: number;
  name: string;
  type: string;
}

export default function RoomCalendarPage() {
  const router = useRouter();
  const params = useParams();
  const accommodationId = params.id as string;
  const roomId = params.roomId as string;

  const { user, isAuthenticated, isLoading: authLoading } = useAuthStore();

  const [month, setMonth] = useState(new Date());
  const [room, setRoom] = useState<Room | null>(null);
  const [calendar, setCalendar] = useState<CalendarDay[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [status, setStatus] = useState<DayStatus>('blocked');

  const fetchRoom = useCallback(async () => {
    const { data } = await api.get(`/accommodations/${accommodationId}/rooms/${roomId}`);
    setRoom(data);
  }, [accommodationId, roomId]);

  const fetchCalendar = useCallback(async () => {
    const start_date = format(startOfMonth(month), 'yyyy-MM-dd');
    const end_date = format(endOfMonth(addMonths(month, 1)), 'yyyy-MM-dd');

    const { data } = await api.get(`/accommodations/${accommodationId}/rooms/${roomId}/calendar`, {
      params: { start_date, end_date },
    });

    setCalendar(data.calendar || []);
  }, [accommodationId, roomId, month]);

  useEffect(() => {
    if (authLoading) return;
    if (!isAuthenticated || !user || user.role !== 'host') {
      router.push('/auth/login');
      return;
    }

    const load = async () => {
      try {
        setLoading(true);
        setError(null);
        await Promise.all([fetchRoom(), fetchCalendar()]);
      } catch (err: any) {
        setError(err.response?.data?.message || 'Erreur lors du chargement du calendrier');
      } finally {
        setLoading(false);
      }
    };

    load();
  }, [authLoading, isAuthenticated, user, router, fetchRoom, fetchCalendar]);

  useEffect(() => {
    if (!loading) {
      fetchCalendar().catch(() => {
        setError('Impossible de rafraîchir le calendrier');
      });
    }
  }, [month]);

  const dateMap = useMemo(() => {
    const map = new Map<string, DayStatus>();
    calendar.forEach((d) => map.set(d.date, d.status));
    return map;
  }, [calendar]);

  const occupiedDates = useMemo(
    () => calendar.filter((d) => d.status === 'occupied').map((d) => new Date(`${d.date}T12:00:00`)),
    [calendar]
  );

  const blockedDates = useMemo(
    () => calendar.filter((d) => d.status === 'blocked').map((d) => new Date(`${d.date}T12:00:00`)),
    [calendar]
  );

  const maintenanceDates = useMemo(
    () => calendar.filter((d) => d.status === 'maintenance').map((d) => new Date(`${d.date}T12:00:00`)),
    [calendar]
  );

  const handleBulkUpdate = async () => {
    if (!startDate || !endDate) {
      setError('Veuillez renseigner la date de début et de fin');
      return;
    }

    if (endDate < startDate) {
      setError('La date de fin doit être après la date de début');
      return;
    }

    try {
      setSaving(true);
      setError(null);

      await api.post(`/accommodations/${accommodationId}/rooms/${roomId}/availability/bulk`, {
        start_date: startDate,
        end_date: endDate,
        status,
      });

      await fetchCalendar();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erreur lors de la mise à jour des disponibilités');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen">
        <Header />
        <main className="container mx-auto px-4 py-12">
          <LoadingSpinner />
        </main>
        <Footer />
      </div>
    );
  }

  return (
    <div className="min-h-screen">
      <Header />
      <main className="container mx-auto px-4 py-8 space-y-6">
        <div>
          <Link
            href={`/dashboard/host/accommodations/${accommodationId}/rooms`}
            className="text-primary hover:underline"
          >
            ← Retour aux chambres
          </Link>
        </div>

        <div>
          <h1 className="text-3xl font-bold">Calendrier de chambre</h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            {room?.name || 'Chambre'} — {room?.type || ''}
          </p>
        </div>

        {error && (
          <div className="bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 p-4 rounded-lg">
            {error}
          </div>
        )}

        <div className="card p-4 space-y-4">
          <h2 className="text-lg font-semibold">Bloquer / débloquer une période</h2>

          <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            />
            <input
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            />
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value as DayStatus)}
              className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            >
              <option value="blocked">Bloqué</option>
              <option value="available">Disponible</option>
              <option value="maintenance">Maintenance</option>
            </select>
            <button
              onClick={handleBulkUpdate}
              disabled={saving}
              className="btn-primary disabled:opacity-50"
            >
              {saving ? 'Mise à jour...' : 'Appliquer'}
            </button>
          </div>

          <p className="text-xs text-gray-500 dark:text-gray-400">
            Astuce: sélectionnez un jour dans le calendrier pour préremplir la plage.
          </p>
        </div>

        <div className="card p-4">
          <style>{`
            .rdp-day_blocked .rdp-day_button { background: #ef4444; color: white; }
            .rdp-day_occupied .rdp-day_button { background: #f59e0b; color: white; }
            .rdp-day_maintenance .rdp-day_button { background: #6b7280; color: white; }
          `}</style>

          <DayPicker
            mode="single"
            locale={frLocale}
            month={month}
            onMonthChange={setMonth}
            showOutsideDays={false}
            modifiers={{
              blocked: blockedDates,
              occupied: occupiedDates,
              maintenance: maintenanceDates,
            }}
            modifiersClassNames={{
              blocked: 'rdp-day_blocked',
              occupied: 'rdp-day_occupied',
              maintenance: 'rdp-day_maintenance',
            }}
            onDayClick={(day) => {
              const selected = format(day, 'yyyy-MM-dd');
              setStartDate(selected);
              setEndDate(selected);
              const currentStatus = dateMap.get(selected);
              if (currentStatus && currentStatus !== 'occupied') {
                setStatus(currentStatus);
              }
            }}
          />

          <div className="mt-4 flex flex-wrap gap-3 text-sm">
            <span className="inline-flex items-center gap-2"><span className="w-3 h-3 rounded bg-red-500" /> Bloqué</span>
            <span className="inline-flex items-center gap-2"><span className="w-3 h-3 rounded bg-amber-500" /> Occupé (réservation)</span>
            <span className="inline-flex items-center gap-2"><span className="w-3 h-3 rounded bg-gray-500" /> Maintenance</span>
            <span className="inline-flex items-center gap-2"><span className="w-3 h-3 rounded bg-green-500" /> Disponible</span>
          </div>
        </div>
      </main>
      <Footer />
    </div>
  );
}
