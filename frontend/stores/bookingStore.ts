import { create } from 'zustand';

interface BookingDraft {
  roomId: number | null;
  accommodationId: number | null;
  checkIn: Date | null;
  checkOut: Date | null;
  guests: number;
  specialRequests: string;
}

interface BookingStore {
  draft: BookingDraft;
  setDates: (checkIn: Date, checkOut: Date) => void;
  setRoom: (roomId: number, accommodationId: number) => void;
  setGuests: (n: number) => void;
  setSpecialRequests: (v: string) => void;
  reset: () => void;
}

const initialDraft: BookingDraft = {
  roomId: null,
  accommodationId: null,
  checkIn: null,
  checkOut: null,
  guests: 1,
  specialRequests: '',
};

export const useBookingStore = create<BookingStore>((set) => ({
  draft: initialDraft,

  setDates: (checkIn, checkOut) =>
    set((s) => ({ draft: { ...s.draft, checkIn, checkOut } })),

  setRoom: (roomId, accommodationId) =>
    set((s) => ({ draft: { ...s.draft, roomId, accommodationId } })),

  setGuests: (guests) =>
    set((s) => ({ draft: { ...s.draft, guests } })),

  setSpecialRequests: (specialRequests) =>
    set((s) => ({ draft: { ...s.draft, specialRequests } })),

  reset: () => set({ draft: initialDraft }),
}));
