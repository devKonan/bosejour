'use client';

import { EventTheme } from '@/stores/appSettingsStore';

const THEMES: Record<
  Exclude<EventTheme, 'default'>,
  { label: string; icon: string; bg: string; text: string }
> = {
  noel: {
    label: 'Joyeux Noël !',
    icon: '🎄',
    bg: 'linear-gradient(90deg, #C41E3A 0%, #006400 100%)',
    text: '#ffffff',
  },
  nouvel_an: {
    label: 'Bonne Année !',
    icon: '🎆',
    bg: 'linear-gradient(90deg, #0f0c29, #302b63, #24243e)',
    text: '#FFD700',
  },
  paques: {
    label: 'Joyeuses Pâques !',
    icon: '🐣',
    bg: 'linear-gradient(90deg, #f9a8d4, #86efac)',
    text: '#1f2937',
  },
};

export default function ThemeBanner({ theme }: { theme: EventTheme }) {
  if (theme === 'default') return null;
  const t = THEMES[theme];
  return (
    <div
      style={{ background: t.bg, color: t.text }}
      className="w-full py-1.5 text-center text-sm font-semibold tracking-wide select-none"
    >
      {t.icon} {t.label} {t.icon}
    </div>
  );
}
