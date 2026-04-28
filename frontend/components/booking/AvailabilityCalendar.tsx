'use client';

import { useState, useMemo } from 'react';
import { DayPicker, type DateRange } from 'react-day-picker';
import { fr as frLocale } from 'react-day-picker/locale';
import { fr } from 'date-fns/locale';
import {
  format,
  addDays,
  startOfMonth,
  endOfMonth,
  addMonths,
  differenceInCalendarDays,
} from 'date-fns';
import { useRoomCalendar } from '@/hooks/useCalendar';
import { formatPrice } from '@/lib/utils';
import 'react-day-picker/style.css';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Props {
  roomId: number;
  basePrice?: number;
  /** Mode sélection de plage (formulaire réservation) */
  mode?: 'range' | 'view';
  selected?: DateRange;
  onSelect?: (range: DateRange | undefined) => void;
  /** Mode view — legacy (affichage statut seul) */
  onDateSelect?: (date: Date) => void;
  className?: string;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function getNights(range?: DateRange): number {
  if (!range?.from || !range?.to) return 0;
  return differenceInCalendarDays(range.to, range.from);
}

// ─── Composant principal ──────────────────────────────────────────────────────

export default function AvailabilityCalendar({
  roomId,
  basePrice,
  mode = 'range',
  selected,
  onSelect,
  onDateSelect,
  className = '',
}: Props) {
  const [month, setMonth] = useState(new Date());
  const { data, isFetching } = useRoomCalendar(roomId, month);

  // Map date → statut pour lookup O(1)
  const calendarMap = useMemo(() => {
    const map = new Map<string, { status: string; price: number | null }>();
    data?.calendar?.forEach((d) => {
      map.set(d.date, { status: d.status, price: d.price_override });
    });
    return map;
  }, [data]);

  // Dates désactivées (occupé, bloqué)
  const disabledDates = useMemo(() => {
    const disabled: Date[] = [];
    const start = startOfMonth(month);
    const end = endOfMonth(addMonths(month, 1));
    let cursor = new Date(start);
    while (cursor <= end) {
      const key = format(cursor, 'yyyy-MM-dd');
      const entry = calendarMap.get(key);
      if (entry && (entry.status === 'occupied' || entry.status === 'blocked')) {
        disabled.push(new Date(cursor));
      }
      cursor = addDays(cursor, 1);
    }
    return disabled;
  }, [calendarMap, month]);

  // Modificateurs de style
  const modifiers = useMemo(() => ({
    occupied: disabledDates,
    today: [new Date()],
  }), [disabledDates]);

  const modifiersClassNames = {
    occupied: 'rdp-occupied',
    today: 'rdp-today-custom',
  };

  const nights = getNights(selected);
  const totalPrice = data?.base_price != null
    ? (data.base_price * nights)
    : (basePrice != null ? basePrice * nights : null);

  // ── Mode vue simple (AvailabilityCalendar existant) ───────────────────────
  if (mode === 'view') {
    return (
      <div className={`bg-white dark:bg-gray-800 rounded-lg p-4 ${className}`}>
        <div className="relative">
          {isFetching && (
            <div className="absolute inset-0 bg-white/60 dark:bg-gray-800/60 z-10 flex items-center justify-center rounded-lg">
              <span className="text-sm text-gray-500">Mise à jour...</span>
            </div>
          )}
          <DayPicker
            mode="single"
            month={month}
            onMonthChange={setMonth}
            locale={frLocale}
            disabled={[{ before: new Date() }, ...disabledDates]}
            modifiers={modifiers}
            modifiersClassNames={modifiersClassNames}
            onDayClick={(day) => onDateSelect?.(day)}
            showOutsideDays={false}
          />
        </div>

        <CalendarLegend />
      </div>
    );
  }

  // ── Mode sélection de plage ────────────────────────────────────────────────
  return (
    <div className={`bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 ${className}`}>
      {/* CSS inline pour les jours occupés */}
      <style>{`
        .rdp-occupied { text-decoration: line-through; opacity: 0.4; pointer-events: none; }
        .rdp-today-custom .rdp-day_button { font-weight: 700; }
      `}</style>

      <div className="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 className="font-semibold text-gray-900 dark:text-white">
          Choisissez vos dates
        </h3>
        {isFetching && (
          <span className="text-xs text-gray-400 animate-pulse">Chargement...</span>
        )}
      </div>

      <div className="p-4">
        <DayPicker
          mode="range"
          selected={selected}
          onSelect={onSelect}
          month={month}
          onMonthChange={setMonth}
          locale={frLocale}
          numberOfMonths={2}
          disabled={[{ before: addDays(new Date(), 0) }, ...disabledDates]}
          modifiers={modifiers}
          modifiersClassNames={modifiersClassNames}
          showOutsideDays={false}
          pagedNavigation
        />
      </div>

      {/* Récapitulatif prix */}
      {nights > 0 && totalPrice != null && (
        <div className="px-4 pb-4 pt-2 border-t border-gray-100 dark:border-gray-700">
          <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
            <span>
              {formatPrice(data?.base_price ?? basePrice ?? 0)} FCFA × {nights} nuit{nights > 1 ? 's' : ''}
            </span>
            <span className="font-bold text-gray-900 dark:text-white text-base">
              {formatPrice(totalPrice)} FCFA
            </span>
          </div>
          {selected?.from && selected?.to && (
            <p className="text-xs text-gray-400 mt-1">
              {format(selected.from, 'dd MMM', { locale: fr })} →{' '}
              {format(selected.to, 'dd MMM yyyy', { locale: fr })}
            </p>
          )}
        </div>
      )}

      <div className="px-4 pb-4">
        <CalendarLegend />
      </div>
    </div>
  );
}

// ─── Légende ─────────────────────────────────────────────────────────────────

function CalendarLegend() {
  return (
    <div className="flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400 mt-2">
      <span className="flex items-center gap-1.5">
        <span className="w-3 h-3 rounded-full bg-white border border-gray-300" />
        Disponible
      </span>
      <span className="flex items-center gap-1.5">
        <span className="w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600 line-through" />
        Occupé
      </span>
    </div>
  );
}
