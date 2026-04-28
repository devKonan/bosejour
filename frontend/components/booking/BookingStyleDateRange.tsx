'use client';

import { useState, useRef, useEffect } from 'react';
import {
  format,
  addMonths,
  subMonths,
  startOfMonth,
  endOfMonth,
  startOfWeek,
  endOfWeek,
  startOfDay,
  addDays,
  isSameMonth,
  isSameDay,
  isBefore,
  isWithinInterval,
  isToday,
  differenceInDays,
} from 'date-fns';
import { fr } from 'date-fns/locale';
import { Calendar as CalendarIcon } from 'lucide-react';

const WEEKDAYS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

export interface BookingStyleDateRangeProps {
  checkIn: Date | null;
  checkOut: Date | null;
  onChange: (checkIn: Date | null, checkOut: Date | null) => void;
  minDate?: Date;
  minNights?: number;
  disabledDates?: string[]; // YYYY-MM-DD
  placeholderArrival?: string;
  placeholderDeparture?: string;
  className?: string;
  /** Si true, le popover est toujours visible (inline), pas au clic */
  inline?: boolean;
}

export default function BookingStyleDateRange({
  checkIn,
  checkOut,
  onChange,
  minDate = new Date(),
  minNights = 1,
  disabledDates = [],
  placeholderArrival = 'Arrivée',
  placeholderDeparture = 'Départ',
  className = '',
  inline = false,
}: BookingStyleDateRangeProps) {
  const [open, setOpen] = useState(false);
  const [hoverDate, setHoverDate] = useState<Date | null>(null);
  const [firstMonth, setFirstMonth] = useState(() => {
    const base = checkIn || minDate;
    return startOfMonth(base);
  });
  const containerRef = useRef<HTMLDivElement>(null);

  const disabledSet = new Set(disabledDates);

  const isDisabled = (d: Date) => {
    if (isBefore(startOfDay(d), startOfDay(minDate))) return true;
    const key = format(d, 'yyyy-MM-dd');
    return disabledSet.has(key);
  };

  const handleDayClick = (d: Date) => {
    if (isDisabled(d)) return;
    if (!checkIn || (checkIn && checkOut)) {
      onChange(d, null);
      setFirstMonth(startOfMonth(d));
    } else {
      if (isBefore(d, checkIn) || isSameDay(d, checkIn)) {
        onChange(d, null);
        return;
      }
      const nights = differenceInDays(d, checkIn);
      if (nights < minNights) {
        const newOut = addDays(checkIn, minNights);
        onChange(checkIn, newOut);
      } else {
        onChange(checkIn, d);
      }
    }
  };

  const handleMouseEnter = (d: Date) => {
    if (isDisabled(d)) return;
    setHoverDate(d);
  };

  const handleMouseLeave = () => setHoverDate(null);

  const isInRange = (d: Date) => {
    if (!checkIn) return false;
    const end = checkOut || hoverDate;
    if (!end || isBefore(end, checkIn)) return false;
    return isWithinInterval(d, { start: checkIn, end }) && !isSameDay(d, end);
  };

  const isStart = (d: Date) => checkIn ? isSameDay(d, checkIn) : false;
  const isEnd = (d: Date) => checkOut ? isSameDay(d, checkOut) : false;
  const isRangeStart = (d: Date) => {
    if (!checkIn) return false;
    const end = checkOut || hoverDate;
    if (!end || isBefore(end, checkIn)) return false;
    return isSameDay(d, checkIn);
  };
  const isRangeEnd = (d: Date) => {
    if (!checkIn) return false;
    const end = checkOut || hoverDate;
    if (!end || isBefore(end, checkIn)) return false;
    return isSameDay(d, end);
  };

  useEffect(() => {
    if (!open) return;
    const handleClickOutside = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [open]);

  const secondMonth = addMonths(firstMonth, 1);

  const renderMonth = (monthStart: Date) => {
    const monthEnd = endOfMonth(monthStart);
    const calStart = startOfWeek(monthStart, { weekStartsOn: 1 });
    const calEnd = endOfWeek(monthEnd, { weekStartsOn: 1 });
    const days: Date[] = [];
    let d = calStart;
    while (d <= calEnd) {
      days.push(d);
      d = addDays(d, 1);
    }
    const rows: Date[][] = [];
    for (let i = 0; i < days.length; i += 7) {
      rows.push(days.slice(i, i + 7));
    }

    return (
      <div key={monthStart.getTime()} className="p-3">
        <div className="text-center font-semibold text-gray-800 dark:text-gray-200 mb-2 capitalize">
          {format(monthStart, 'MMMM yyyy', { locale: fr })}
        </div>
        <div className="grid grid-cols-7 gap-0.5">
          {WEEKDAYS.map((wd) => (
            <div
              key={wd}
              className="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-1"
            >
              {wd}
            </div>
          ))}
          {rows.flatMap((row) =>
            row.map((day) => {
              const disabled = isDisabled(day);
              const inRange = isInRange(day);
              const start = isRangeStart(day);
              const end = isRangeEnd(day);
              const otherMonth = !isSameMonth(day, monthStart);
              return (
                <button
                  key={day.getTime()}
                  type="button"
                  disabled={disabled}
                  onClick={() => handleDayClick(day)}
                  onMouseEnter={() => handleMouseEnter(day)}
                  onMouseLeave={handleMouseLeave}
                  className={`
                    w-9 h-9 text-sm rounded flex items-center justify-center
                    ${otherMonth ? 'text-gray-300 dark:text-gray-600' : 'text-gray-900 dark:text-gray-100'}
                    ${disabled ? 'cursor-not-allowed opacity-40' : 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700'}
                    ${isToday(day) && !start && !end ? 'ring-1 ring-primary font-semibold' : ''}
                    ${inRange ? 'bg-primary/15 dark:bg-primary/20' : ''}
                    ${start ? 'rounded-r-none bg-primary text-white hover:bg-primary hover:text-white font-semibold' : ''}
                    ${end ? 'rounded-l-none bg-primary text-white hover:bg-primary hover:text-white font-semibold' : ''}
                    ${start && end ? 'rounded bg-primary text-white' : ''}
                  `}
                >
                  {format(day, 'd')}
                </button>
              );
            })
          )}
        </div>
      </div>
    );
  };

  const trigger = (
    <button
      type="button"
      onClick={() => !inline && setOpen((o) => !o)}
      className={`
        w-full flex items-stretch rounded-xl border border-gray-300 dark:border-gray-600
        bg-white dark:bg-gray-800 overflow-hidden
        ${!inline ? 'cursor-pointer hover:border-primary/50 dark:hover:border-primary/50' : ''}
        ${className}
      `}
    >
      <div className="flex-1 flex items-center gap-2 px-4 py-3 border-r border-gray-200 dark:border-gray-700">
        <CalendarIcon className="w-5 h-5 text-gray-500 dark:text-gray-400 flex-shrink-0" />
        <div className="text-left">
          <div className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
            Arrivée
          </div>
          <div className="text-sm font-semibold text-gray-900 dark:text-white">
            {checkIn ? format(checkIn, 'EEE d MMM', { locale: fr }) : placeholderArrival}
          </div>
        </div>
      </div>
      <div className="flex-1 flex items-center gap-2 px-4 py-3">
        <CalendarIcon className="w-5 h-5 text-gray-500 dark:text-gray-400 flex-shrink-0" />
        <div className="text-left">
          <div className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
            Départ
          </div>
          <div className="text-sm font-semibold text-gray-900 dark:text-white">
            {checkOut ? format(checkOut, 'EEE d MMM', { locale: fr }) : placeholderDeparture}
          </div>
        </div>
      </div>
    </button>
  );

  const calendarContent = (
    <div className="flex flex-wrap justify-center gap-2 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700">
      <div className="flex items-center justify-between w-full px-3 pt-3">
        <button
          type="button"
          onClick={() => setFirstMonth((m) => subMonths(m, 1))}
          className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"
          aria-label="Mois précédent"
        >
          ←
        </button>
        <span className="text-sm font-medium text-gray-600 dark:text-gray-400">
          {minNights > 1 ? `${minNights} nuit${minNights > 1 ? 's' : ''} minimum` : 'Sélectionnez vos dates'}
        </span>
        <button
          type="button"
          onClick={() => setFirstMonth((m) => addMonths(m, 1))}
          className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300"
          aria-label="Mois suivant"
        >
          →
        </button>
      </div>
      <div className="flex flex-nowrap gap-0 pb-4">
        {renderMonth(firstMonth)}
        {renderMonth(secondMonth)}
      </div>
    </div>
  );

  if (inline) {
    return (
      <div ref={containerRef} className="space-y-4">
        {trigger}
        {calendarContent}
      </div>
    );
  }

  return (
    <div ref={containerRef} className="relative">
      {trigger}
      {open && (
        <div className="absolute left-0 top-full mt-2 z-50">
          {calendarContent}
        </div>
      )}
    </div>
  );
}
