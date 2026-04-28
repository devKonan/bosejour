'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import api from '@/lib/api';
import { useAuthStore } from '@/stores/authStore';
import { useSearchStore } from '@/stores/searchStore';
import Header from '@/components/common/Header';
import Footer from '@/components/common/Footer';
import AccommodationCard from '@/components/accommodation/AccommodationCard';
import HeroSection from '@/components/common/HeroSection';
import Pagination from '@/components/common/Pagination';
import { FadeIn, StaggerContainer, StaggerItem } from '@/components/common/animations';

interface Accommodation {
  id: number;
  name: string;
  slug: string;
  type: string;
  description: string;
  city: string;
  price_per_night: number;
  rating: number;
  total_reviews: number;
  images: Array<{ url: string; is_primary: boolean }>;
}

export default function Home() {
  const router = useRouter();
  const { user, isAuthenticated, isLoading } = useAuthStore();
  const { session } = useSearchStore();
  const [accommodations, setAccommodations] = useState<Accommodation[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchParams, setSearchParams] = useState<{
    search?: string;
    checkIn?: string;
    checkOut?: string;
    guests?: number;
    city?: string;
    type?: string;
  }>({});

  useEffect(() => {
    if (session && Object.keys(searchParams).length === 0) {
      setSearchParams({ ...session });
    }
  }, [session]);
  const [currentPage, setCurrentPage] = useState(1);
  const [pagination, setPagination] = useState({
    total: 0,
    per_page: 10,
    current_page: 1,
    last_page: 1,
  });

  useEffect(() => {
    // Attendre que le chargement initial soit terminé
    if (isLoading) {
      return;
    }

    // Rediriger les hôtes vers leur dashboard
    if (isAuthenticated && user?.role === 'host') {
      router.push('/dashboard/host');
      return;
    }
    
    // Charger les hébergements pour les utilisateurs non-hôtes
    if (user?.role !== 'host') {
      fetchAccommodations();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchParams, currentPage, isAuthenticated, isLoading, user?.role]);

  const fetchAccommodations = async () => {
    try {
      setLoading(true);
      const params: any = { per_page: 10, page: currentPage };
      if (searchParams.search) params.search = searchParams.search;
      if (searchParams.checkIn) params.check_in = searchParams.checkIn;
      if (searchParams.checkOut) params.check_out = searchParams.checkOut;
      if (searchParams.guests) params.guests = searchParams.guests;
      if (searchParams.city) params.city = searchParams.city;
      if (searchParams.type) params.type = searchParams.type;
      
      const response = await api.get('/accommodations', { params });
      
      // Gérer la réponse paginée Laravel
      if (response.data.data && Array.isArray(response.data.data)) {
        setAccommodations(response.data.data);
        setPagination({
          total: response.data.total || 0,
          per_page: response.data.per_page || 10,
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
        });
      } else if (Array.isArray(response.data)) {
        setAccommodations(response.data);
        setPagination({
          total: response.data.length,
          per_page: 10,
          current_page: 1,
          last_page: 1,
        });
      }
    } catch (error) {
      console.error('Error fetching accommodations:', error);
    } finally {
      setLoading(false);
    }
  };

  const handlePageChange = (page: number) => {
    setCurrentPage(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Afficher un chargement pendant l'initialisation de l'authentification
  if (isLoading) {
    return (
      <div className="min-h-screen">
        <Header />
        <main className="container mx-auto px-4 py-8 max-w-7xl">
          <div className="text-center py-12">
            <p className="text-gray-600 dark:text-gray-400">Chargement...</p>
          </div>
        </main>
      </div>
    );
  }

  return (
    <div className="min-h-screen">
      {/* Header avec position absolue pour qu'il soit au-dessus du Hero */}
      <div className="relative z-50">
        <Header />
      </div>
      
      {/* Hero Section avec background image */}
      <HeroSection
        onSearch={(params) => {
          setSearchParams(params);
          setCurrentPage(1); // Réinitialiser à la page 1 lors d'une nouvelle recherche
        }}
        initialValues={searchParams}
      />
      
      {/* Liste des hébergements - Code inchangé */}
      <main className="container mx-auto px-4 py-8 max-w-7xl">
        {loading ? (
          <div className="text-center py-12">
            <p className="text-gray-600 dark:text-gray-400">Chargement...</p>
          </div>
        ) : accommodations.length === 0 ? (
          <div className="text-center py-12">
            <p className="text-gray-600 dark:text-gray-400">
              Aucun hébergement trouvé
            </p>
          </div>
        ) : (
          <>
            <StaggerContainer className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
              {accommodations.map((acc) => (
                <StaggerItem key={acc.id}>
                  <AccommodationCard accommodation={acc} />
                </StaggerItem>
              ))}
            </StaggerContainer>
            <FadeIn delay={0.3}>
              <Pagination
                currentPage={pagination.current_page}
                totalPages={pagination.last_page}
                onPageChange={handlePageChange}
                totalItems={pagination.total}
                itemsPerPage={pagination.per_page}
              />
            </FadeIn>
          </>
        )}
      </main>
      <Footer />
    </div>
  );
}

