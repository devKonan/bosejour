import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/api';
import type {
  Booking,
  BookingFilters,
  BookingHistory,
  BookingCreatePayload,
} from '@/types/booking';

async function triggerNotification(event: string, bookingId: number, extra?: Record<string, unknown>) {
  try {
    await api.post('/notifications/trigger', { event, booking_id: bookingId, ...extra });
  } catch {
    // Silencieux — une notif ratée ne bloque pas l'UX
  }
}

// ─── Clés de cache centralisées ──────────────────────────────────────────────
export const bookingKeys = {
  all: ['bookings'] as const,
  lists: () => [...bookingKeys.all, 'list'] as const,
  list: (filters: BookingFilters) => [...bookingKeys.lists(), filters] as const,
  details: () => [...bookingKeys.all, 'detail'] as const,
  detail: (id: number) => [...bookingKeys.details(), id] as const,
  history: (id: number) => [...bookingKeys.all, 'history', id] as const,
};

// ─── Helpers API ─────────────────────────────────────────────────────────────

interface PaginatedResponse<T> {
  data: T[];
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

// ─── Hooks de lecture ─────────────────────────────────────────────────────────

export function useBookings(filters: BookingFilters = {}) {
  return useQuery({
    queryKey: bookingKeys.list(filters),
    queryFn: async (): Promise<PaginatedResponse<Booking>> => {
      const params: Record<string, unknown> = { per_page: 10, ...filters };
      if (params.status === 'all') delete params.status;
      if (params.payment_status === 'all') delete params.payment_status;
      if (params.period === 'all') delete params.period;
      if (!params.search) delete params.search;

      const { data } = await api.get('/bookings', { params });

      // Normaliser la réponse paginée Laravel
      if (data?.data && Array.isArray(data.data)) return data;
      if (Array.isArray(data)) {
        return { data, total: data.length, per_page: 10, current_page: 1, last_page: 1 };
      }
      return { data: [], total: 0, per_page: 10, current_page: 1, last_page: 1 };
    },
    staleTime: 30_000,
  });
}

export function useBooking(id: number | null) {
  return useQuery({
    queryKey: bookingKeys.detail(id!),
    queryFn: async (): Promise<Booking> => {
      const { data } = await api.get(`/bookings/${id}`);
      return data;
    },
    enabled: !!id,
    staleTime: 60_000,
  });
}

export function useBookingHistory(id: number | null) {
  return useQuery({
    queryKey: bookingKeys.history(id!),
    queryFn: async (): Promise<BookingHistory[]> => {
      const { data } = await api.get(`/bookings/${id}/history`);
      return data.history ?? [];
    },
    enabled: !!id,
    staleTime: 60_000,
  });
}

// ─── Hooks de mutation ────────────────────────────────────────────────────────

export function useCreateBooking() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: BookingCreatePayload): Promise<Booking> => {
      const { data } = await api.post('/bookings', payload);
      return data;
    },
    onSuccess: (booking) => {
      qc.invalidateQueries({ queryKey: bookingKeys.lists() });
      triggerNotification('booking.created', booking.id);
    },
  });
}

export function useCancelBooking() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async ({
      id,
      reason,
    }: {
      id: number;
      reason?: string;
    }): Promise<{ booking: Booking; credit_created: unknown }> => {
      const { data } = await api.post(`/bookings/${id}/cancel`, { reason });
      return data;
    },
    onMutate: async ({ id }) => {
      await qc.cancelQueries({ queryKey: bookingKeys.detail(id) });
      const prev = qc.getQueryData<Booking>(bookingKeys.detail(id));
      // Optimistic update
      qc.setQueryData<Booking>(bookingKeys.detail(id), (old) =>
        old ? { ...old, status: 'cancelled' } : old
      );
      return { prev };
    },
    onError: (_err, { id }, ctx) => {
      if (ctx?.prev) {
        qc.setQueryData(bookingKeys.detail(id), ctx.prev);
      }
    },
    onSettled: (_data, _err, { id }) => {
      qc.invalidateQueries({ queryKey: bookingKeys.detail(id) });
      qc.invalidateQueries({ queryKey: bookingKeys.lists() });
    },
    onSuccess: (_data, { id }) => {
      triggerNotification('booking.cancelled', id);
    },
  });
}

export function useConfirmBooking() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number): Promise<Booking> => {
      const { data } = await api.put(`/bookings/${id}`, { status: 'confirmed' });
      return data;
    },
    onSuccess: (_data, id) => {
      qc.invalidateQueries({ queryKey: bookingKeys.detail(id) });
      qc.invalidateQueries({ queryKey: bookingKeys.lists() });
      triggerNotification('booking.confirmed', id);
    },
  });
}

export function useCompleteBooking() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number): Promise<Booking> => {
      const { data } = await api.post(`/bookings/${id}/complete`);
      return data;
    },
    onSuccess: (_data, id) => {
      qc.invalidateQueries({ queryKey: bookingKeys.detail(id) });
      qc.invalidateQueries({ queryKey: bookingKeys.lists() });
      triggerNotification('booking.completed', id);
    },
  });
}
