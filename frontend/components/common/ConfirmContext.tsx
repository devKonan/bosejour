'use client';

import {
  createContext,
  useCallback,
  useContext,
  useState,
  useRef,
  ReactNode,
} from 'react';
import ConfirmModal, { ConfirmModalProps } from './ConfirmModal';

export interface ConfirmOptions {
  title: string;
  message?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  variant?: 'default' | 'danger';
}

type ConfirmResolve = (value: boolean) => void;

const ConfirmContext = createContext<((options: ConfirmOptions) => Promise<boolean>) | null>(null);

export function ConfirmProvider({ children }: { children: ReactNode }) {
  const [state, setState] = useState<ConfirmModalProps & { open: boolean }>({
    open: false,
    title: '',
    message: undefined,
    confirmLabel: 'Confirmer',
    cancelLabel: 'Annuler',
    variant: 'default',
    loading: false,
    onConfirm: () => {},
    onCancel: () => {},
  });
  const resolveRef = useRef<ConfirmResolve | null>(null);

  const confirm = useCallback((options: ConfirmOptions) => {
    return new Promise<boolean>((resolve) => {
      resolveRef.current = resolve;
      setState({
        open: true,
        title: options.title,
        message: options.message,
        confirmLabel: options.confirmLabel ?? 'Confirmer',
        cancelLabel: options.cancelLabel ?? 'Annuler',
        variant: options.variant ?? 'default',
        loading: false,
        onConfirm: () => {
          resolveRef.current?.(true);
          resolveRef.current = null;
          setState((s) => ({ ...s, open: false }));
        },
        onCancel: () => {
          resolveRef.current?.(false);
          resolveRef.current = null;
          setState((s) => ({ ...s, open: false }));
        },
      });
    });
  }, []);

  return (
    <ConfirmContext.Provider value={confirm}>
      {children}
      <ConfirmModal
        open={state.open}
        title={state.title}
        message={state.message}
        confirmLabel={state.confirmLabel}
        cancelLabel={state.cancelLabel}
        variant={state.variant}
        loading={state.loading}
        onConfirm={state.onConfirm}
        onCancel={state.onCancel}
      />
    </ConfirmContext.Provider>
  );
}

export function useConfirm() {
  const context = useContext(ConfirmContext);
  if (!context) {
    throw new Error('useConfirm must be used within ConfirmProvider');
  }
  return context;
}
