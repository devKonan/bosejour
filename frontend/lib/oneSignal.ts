// OneSignal Web Push SDK v16 — service wrapper
// Responsabilités frontend :
//   1. Demander la permission navigateur
//   2. Lier l'appareil à l'utilisateur connecté (login/logout)
//   3. Taguer l'utilisateur pour la segmentation côté OneSignal

declare global {
  interface Window {
    OneSignalDeferred?: ((os: OneSignalInstance) => void | Promise<void>)[];
  }
}

interface OneSignalInstance {
  init: (config: { appId: string }) => Promise<void>;
  login: (externalId: string) => Promise<void>;
  logout: () => Promise<void>;
  Notifications: {
    permission: boolean;
    requestPermission: () => Promise<boolean>;
  };
  User: {
    addEmail: (email: string) => Promise<void>;
    addTag: (key: string, value: string) => void;
    addTags: (tags: Record<string, string>) => void;
    removeTag: (key: string) => void;
  };
}

function push(fn: (os: OneSignalInstance) => void | Promise<void>) {
  if (typeof window === 'undefined') return;
  window.OneSignalDeferred = window.OneSignalDeferred || [];
  window.OneSignalDeferred.push(fn);
}

export const oneSignal = {
  /** Demande la permission push navigateur */
  requestPermission(): Promise<boolean> {
    return new Promise((resolve) => {
      push(async (os) => {
        try {
          const granted = await os.Notifications.requestPermission();
          resolve(granted);
        } catch {
          resolve(false);
        }
      });
    });
  },

  /** Lie l'abonnement push à un utilisateur (après login / register) */
  loginUser(userId: string | number) {
    push(async (os) => {
      try {
        await os.login(String(userId));
      } catch {
        // Silencieux — ne pas bloquer l'UX si OneSignal n'est pas dispo
      }
    });
  },

  /** Délie l'abonnement de l'utilisateur (après logout) */
  logoutUser() {
    push(async (os) => {
      try {
        await os.logout();
      } catch {
        // Silencieux
      }
    });
  },

  /** Ajoute des tags de segmentation (rôle, langue…) */
  setTags(tags: Record<string, string>) {
    push((os) => {
      try {
        os.User.addTags(tags);
      } catch {
        // Silencieux
      }
    });
  },

  /** Lie l'email utilisateur pour les envois OneSignal Email */
  syncEmail(email: string) {
    if (!email) return;

    push(async (os) => {
      try {
        await os.User.addEmail(email);
      } catch {
        // Silencieux
      }
    });
  },

  /** Vérifie si la permission est déjà accordée */
  hasPermission(): Promise<boolean> {
    return new Promise((resolve) => {
      push((os) => {
        try {
          resolve(os.Notifications.permission);
        } catch {
          resolve(false);
        }
      });
    });
  },
};
