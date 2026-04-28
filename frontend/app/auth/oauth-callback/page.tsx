'use client';

import { Suspense, useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useAuthStore } from '@/stores/authStore';
import Header from '@/components/common/Header';
import Footer from '@/components/common/Footer';
import { authService } from '@/lib/auth';
import { isController, isAdmin } from '@/lib/userUtils';

// Désactiver le pré-rendu statique car cette page utilise useSearchParams()
export const dynamic = 'force-dynamic';

function OAuthCallbackContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { setUser, setToken } = useAuthStore();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const handleCallback = async () => {
      try {
        // Récupérer les données depuis l'URL (si le backend les passe en query params)
        // Ou faire une requête au backend pour récupérer les données
        const provider = searchParams.get('provider');
        const token = searchParams.get('token');
        const userData = searchParams.get('user');

        if (token && userData) {
          const user = JSON.parse(decodeURIComponent(userData));
          
          // Stocker les données d'authentification
          if (typeof window !== 'undefined') {
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(user));
          }

          // Mettre à jour le store
          setUser(user);
          setToken(token);

          // Rediriger selon le rôle
          const currentUser = user;
          
          if (isController(currentUser)) {
            router.push('/dashboard/admin/inspections');
            return;
          }
          
          if (currentUser?.role === 'host') {
            router.push('/dashboard/host');
          } else if (isAdmin(currentUser)) {
            router.push('/dashboard/admin');
          } else {
            router.push('/');
          }
        } else {
          // Si les données ne sont pas dans l'URL, le backend devrait rediriger vers une page avec ces données
          // Pour l'instant, on affiche une erreur
          setError('Données d\'authentification manquantes. Veuillez réessayer.');
          setLoading(false);
        }
      } catch (err: any) {
        console.error('OAuth callback error:', err);
        setError(err.message || 'Erreur lors de l\'authentification OAuth');
        setLoading(false);
      }
    };

    handleCallback();
  }, [router, searchParams, setUser, setToken]);

  if (loading) {
    return (
      <div className="min-h-screen">
        <Header />
        <main className="container mx-auto px-4 py-12">
          <div className="max-w-md mx-auto card text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
            <p className="text-gray-600 dark:text-gray-400">Connexion en cours...</p>
          </div>
        </main>
        <Footer />
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen">
        <Header />
        <main className="container mx-auto px-4 py-12">
          <div className="max-w-md mx-auto card">
            <div className="bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 p-4 rounded-lg mb-4">
              {error}
            </div>
            <button
              onClick={() => router.push('/auth/login')}
              className="w-full btn-primary"
            >
              Retour à la connexion
            </button>
          </div>
        </main>
        <Footer />
      </div>
    );
  }

  return null;
}

export default function OAuthCallbackPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen">
        <Header />
        <main className="container mx-auto px-4 py-12">
          <div className="max-w-md mx-auto card text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
            <p className="text-gray-600 dark:text-gray-400">Chargement...</p>
          </div>
        </main>
        <Footer />
      </div>
    }>
      <OAuthCallbackContent />
    </Suspense>
  );
}
