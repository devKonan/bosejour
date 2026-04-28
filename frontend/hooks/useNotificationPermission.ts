'use client';

import { useEffect, useRef, useState } from 'react';
import { oneSignal } from '@/lib/oneSignal';

/**
 * Demande la permission push après un délai (n'interrompt pas l'UX).
 * À utiliser juste après une action importante (inscription, première réservation).
 */
export function useNotificationPermission(trigger: boolean, delayMs = 3000) {
  const asked = useRef(false);
  const [granted, setGranted] = useState<boolean | null>(null);

  useEffect(() => {
    if (!trigger || asked.current || typeof window === 'undefined') return;
    if (!('Notification' in window)) return;
    if (Notification.permission === 'denied') return;

    asked.current = true;

    const timer = setTimeout(async () => {
      const result = await oneSignal.requestPermission();
      setGranted(result);
    }, delayMs);

    return () => clearTimeout(timer);
  }, [trigger, delayMs]);

  return granted;
}
