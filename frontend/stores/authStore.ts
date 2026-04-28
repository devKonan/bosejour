import { create } from 'zustand';
import { User, authService } from '@/lib/auth';
import { oneSignal } from '@/lib/oneSignal';

interface AuthState {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (data: any) => Promise<void>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
  setUser: (user: User | null) => void;
  setToken: (token: string | null) => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  token: null,
  isLoading: true,
  isAuthenticated: false,

  login: async (email: string, password: string) => {
    const { user, token } = await authService.login({ email, password });
    try {
      const freshUser = await authService.getCurrentUser();
      const activeUser = freshUser ?? user;
      set({ user: activeUser, token, isAuthenticated: true });
      if (activeUser?.id) {
        oneSignal.loginUser(activeUser.id);
        oneSignal.setTags({ role: activeUser.role ?? 'user' });
        if (activeUser.email) {
          oneSignal.syncEmail(activeUser.email);
        }
      }
    } catch {
      set({ user, token, isAuthenticated: true });
      if (user?.id) {
        oneSignal.loginUser(user.id);
        if (user.email) {
          oneSignal.syncEmail(user.email);
        }
      }
    }
  },

  register: async (data: any) => {
    const { user, token } = await authService.register(data);
    try {
      const freshUser = await authService.getCurrentUser();
      const activeUser = freshUser ?? user;
      set({ user: activeUser, token, isAuthenticated: true });
      if (activeUser?.id) {
        oneSignal.loginUser(activeUser.id);
        oneSignal.setTags({ role: activeUser.role ?? 'user' });
        if (activeUser.email) {
          oneSignal.syncEmail(activeUser.email);
        }
      }
    } catch {
      set({ user, token, isAuthenticated: true });
      if (user?.id) {
        oneSignal.loginUser(user.id);
        if (user.email) {
          oneSignal.syncEmail(user.email);
        }
      }
    }
  },

  logout: async () => {
    oneSignal.logoutUser();
    await authService.logout();
    set({ user: null, token: null, isAuthenticated: false });
  },

  checkAuth: async () => {
    const storedUser = authService.getStoredUser();
    const token = authService.getToken();

    if (storedUser && token) {
      try {
        // Timeout de sécurité pour éviter que l'app reste bloquée
        const timeoutPromise = new Promise((_, reject) => 
          setTimeout(() => reject(new Error('Timeout')), 5000)
        );
        
        const user = await Promise.race([
          authService.getCurrentUser(),
          timeoutPromise
        ]) as User | null;
        
        set({ user, token, isAuthenticated: true, isLoading: false });
        if (user?.id) {
          oneSignal.loginUser(user.id);
          oneSignal.setTags({ role: user.role ?? 'user' });
          if (user.email) {
            oneSignal.syncEmail(user.email);
          }
        }
      } catch {
        // En cas d'erreur, utiliser les données stockées localement
        set({ user: storedUser, token, isAuthenticated: !!storedUser, isLoading: false });
        if (storedUser?.id) {
          oneSignal.loginUser(storedUser.id);
          oneSignal.setTags({ role: storedUser.role ?? 'user' });
          if (storedUser.email) {
            oneSignal.syncEmail(storedUser.email);
          }
        }
      }
    } else {
      set({ user: null, token: null, isAuthenticated: false, isLoading: false });
    }
  },

  setUser: (user: User | null) => {
    set({ user, isAuthenticated: !!user });
  },

  setToken: (token: string | null) => {
    set({ token });
  },
}));

