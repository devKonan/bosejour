import api from './api';

export interface User {
  id: number;
  name: string;
  email: string;
  role: 'user' | 'host' | 'admin';
  phone?: string;
  avatar?: string;
  roles?: Array<{ id: number; name: string; display_name: string }>;
  // Champs spécifiques aux hôtes (remplis lors de l'inscription)
  establishment_name?: string;
  accommodation_type?: string;
  address_line1?: string;
  city?: string;
  phone_fixed?: string;
  whatsapp?: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
  role?: 'user' | 'host';
}

export const authService = {
  async login(credentials: LoginCredentials) {
    const response = await api.post('/login', credentials);
    
    // Vérifier si le 2FA est requis
    if (response.data.requires_2fa) {
      // Retourner une erreur spéciale pour le 2FA
      const error: any = new Error('2FA verification required');
      error.requires_2fa = true;
      error.user_id = response.data.user_id;
      error.temp_token = response.data.temp_token;
      throw error;
    }
    
    const { user, token } = response.data;
    
    if (typeof window !== 'undefined') {
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
    }
    
    return { user, token };
  },

  async register(data: RegisterData) {
    const response = await api.post('/register', data);
    const { user, token } = response.data;
    
    if (typeof window !== 'undefined') {
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
    }
    
    return { user, token };
  },

  async logout() {
    await api.post('/logout');
    if (typeof window !== 'undefined') {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    }
  },

  async getCurrentUser(): Promise<User | null> {
    try {
      const response = await api.get('/me');
      return response.data;
    } catch {
      return null;
    }
  },

  getStoredUser(): User | null {
    if (typeof window === 'undefined') return null;
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
  },

  getToken(): string | null {
    if (typeof window === 'undefined') return null;
    return localStorage.getItem('token');
  },

  /**
   * Rediriger vers le fournisseur OAuth
   */
  redirectToOAuth(provider: 'google' | 'microsoft') {
    const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://api.bosejour.ci/api';
    window.location.href = `${apiUrl}/auth/${provider}/redirect`;
  },

  /**
   * Gérer le callback OAuth (appelé depuis une page de callback)
   */
  async handleOAuthCallback(data: { user: User; token: string; provider: string }) {
    if (typeof window !== 'undefined') {
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
    }
    return { user: data.user, token: data.token };
  },
};

