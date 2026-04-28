'use client';

import { useCallback, useMemo } from 'react';
import { Calendar } from 'lucide-react';

function formatDate(d: Date) {
  return d.toISOString().slice(0, 10);
}

const PRESETS = [
  { label: '7 jours', days: 7 },
  { label: '30 jours', days: 30 },
  { label: '90 jours', days: 90 },
  { label: 'Mois en cours', type: 'current_month' as const },
];

export interface DateRange {
  from: string;
  to: string;
}

interface DateRangeFilterProps {
  from: string;
  to: string;
  onRangeChange: (from: string, to: string) => void;
  label?: string;
  className?: string;
}

export default function DateRangeFilter({
  from,
  to,
  onRangeChange,
  label = 'Période',
  className = '',
}: DateRangeFilterProps) {
  const applyPreset = useCallback(
    (preset: (typeof PRESETS)[number]) => {
      const now = new Date();
      let fromDate: Date;
      let toDate: Date;
      if (preset.type === 'current_month') {
        fromDate = new Date(now.getFullYear(), now.getMonth(), 1);
        toDate = new Date();
      } else {
        toDate = new Date();
        fromDate = new Date(toDate);
        fromDate.setDate(fromDate.getDate() - (preset.days ?? 0));
      }
      onRangeChange(formatDate(fromDate), formatDate(toDate));
    },
    [onRangeChange]
  );

  const handleFromChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const v = e.target.value;
    if (v && to) onRangeChange(v, to);
  };

  const handleToChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const v = e.target.value;
    if (from && v) onRangeChange(from, v);
  };

  return (
    <div className={`flex flex-wrap items-center gap-3 ${className}`}>
      <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
        <Calendar className="w-4 h-4" />
        <span className="text-sm font-medium">{label}</span>
      </div>
      <div className="flex flex-wrap items-center gap-2">
        <input
          type="date"
          value={from}
          onChange={handleFromChange}
          className="input border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1.5 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
        />
        <span className="text-gray-500 dark:text-gray-400">→</span>
        <input
          type="date"
          value={to}
          onChange={handleToChange}
          className="input border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1.5 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
        />
      </div>
      <div className="flex flex-wrap gap-1">
        {PRESETS.map((preset) => (
          <button
            key={preset.label}
            type="button"
            onClick={() => applyPreset(preset)}
            className="px-2.5 py-1 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-primary/10 hover:text-primary transition-colors"
          >
            {preset.label}
          </button>
        ))}
      </div>
    </div>
  );
}

export function useDefaultDateRange(defaultDays = 30): DateRange {
  return useMemo(() => {
    const end = new Date();
    const start = new Date(end);
    start.setDate(start.getDate() - defaultDays);
    return { from: formatDate(start), to: formatDate(end) };
  }, [defaultDays]);
}
