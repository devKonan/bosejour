'use client';

import { useState, useEffect } from 'react';
import { Search, Calendar, Users, Shield, Lock, Headphones, ArrowRight } from 'lucide-react';
import SearchInputWithAutocomplete from './SearchInputWithAutocomplete';
import { useSearchStore } from '@/stores/searchStore';
import { motion, AnimatePresence } from 'framer-motion';
import Image from 'next/image';
import Link from 'next/link';

interface HeroSectionProps {
  onSearch: (params: {
    search?: string;
    checkIn?: string;
    checkOut?: string;
    guests?: number;
    city?: string;
    type?: string;
  }) => void;
  initialValues?: {
    search?: string;
    checkIn?: string;
    checkOut?: string;
    guests?: number;
    city?: string;
    type?: string;
  };
}

export default function HeroSection({ onSearch, initialValues }: HeroSectionProps) {
  const { session, setSearchSession } = useSearchStore();
  const [search, setSearch] = useState(initialValues?.search || session?.search || '');
  const [checkIn, setCheckIn] = useState(initialValues?.checkIn || session?.checkIn || '');
  const [checkOut, setCheckOut] = useState(initialValues?.checkOut || session?.checkOut || '');
  const [guests, setGuests] = useState(initialValues?.guests ?? session?.guests ?? 1);
  const [city, setCity] = useState(initialValues?.city || session?.city || '');
  const [mobileSearchOpen, setMobileSearchOpen] = useState(false);

  useEffect(() => {
    if (session && !initialValues?.checkIn) {
      setCheckIn(session.checkIn || '');
      setCheckOut(session.checkOut || '');
      setGuests(session.guests ?? 1);
      setSearch(session.search || '');
      setCity(session.city || '');
    }
  }, [session]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const params = {
      search: search.trim() || undefined,
      checkIn: checkIn || undefined,
      checkOut: checkOut || undefined,
      guests: guests > 0 ? guests : undefined,
      city: city.trim() || undefined,
    };
    setSearchSession(params);
    onSearch(params);
  };

  // Date minimale = aujourd'hui
  const today = new Date().toISOString().split('T')[0];
  // Date minimale pour check-out = check-in ou aujourd'hui
  const minCheckOut = checkIn || today;

  // Formater la date pour l'affichage
  const formatDate = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
  };

  return (
    <div className="relative w-full h-[75vh] md:h-[90vh] min-h-[500px] md:min-h-[700px] flex items-center pt-16 md:pt-20">
      {/* Background Image */}
      <div className="absolute inset-0 z-0">
        <Image
          src="/images/bg.jpeg"
          alt="Hero background"
          fill
          priority
          className="object-cover"
          quality={90}
        />
        {/* Overlay pour améliorer la lisibilité - gradient plus fort à gauche */}
        <div className="absolute inset-0 bg-gradient-to-r from-black/75 via-black/60 to-black/30" />
      </div>

      {/* Content */}
      <div className="relative z-10 w-full">
        {/* Contenu de gauche (logo, titre, description) */}
        <div className="container mx-auto px-4 md:px-8 lg:px-12 max-w-7xl">
          <div className="max-w-2xl">
            {/* Logo et Tagline */}
            <motion.div
              initial={{ opacity: 0, y: -20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5 }}
              className="mb-8"
            >
              <Link href="/" className="inline-block mb-1">
                <span className="text-3xl md:text-4xl font-bold text-red-600 font-logo tracking-tight">
                  bosejour
                </span>
              </Link>
              <p className="text-sm text-gray-300/90">Votre séjour commence ici...</p>
            </motion.div>

            {/* Titre principal */}
            <motion.h1
              initial={{ opacity: 0, y: -20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.1 }}
              className="text-2xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-3 md:mb-4 leading-tight"
            >
              Votre séjour commence ici...
            </motion.h1>

            {/* Description */}
            <motion.p
              initial={{ opacity: 0, y: -20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.2 }}
              className="text-base md:text-xl text-white mb-4 md:mb-8"
            >
              Trouvez et réservez votre hébergement idéal en Côte d'Ivoire.
            </motion.p>
          </div>
        </div>

        {/* Bouton de recherche mobile - visible uniquement sur mobile */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, delay: 0.3 }}
          className="md:hidden mx-5 mb-4"
        >
          <button
            type="button"
            onClick={() => setMobileSearchOpen(!mobileSearchOpen)}
            className="w-full bg-white rounded-full shadow-xl px-5 py-3.5 flex items-center gap-3 text-left"
          >
            <Search className="w-5 h-5 text-primary flex-shrink-0" />
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-gray-900 truncate">
                {city || search || 'Où allez-vous ?'}
              </p>
              <p className="text-xs text-gray-500 truncate">
                {checkIn ? formatDate(checkIn) : 'Dates'} · {guests} voyageur{guests > 1 ? 's' : ''}
              </p>
            </div>
          </button>
        </motion.div>

        {/* Panneau de recherche mobile dépliable */}
        <AnimatePresence>
          {mobileSearchOpen && (
            <motion.div
              className="md:hidden fixed inset-0 z-[60] bg-white dark:bg-gray-900"
              initial={{ opacity: 0, y: '100%' }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: '100%' }}
              transition={{ type: 'spring', damping: 25, stiffness: 300 }}
            >
              <div className="flex flex-col h-full">
                {/* Header du panneau */}
                <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                  <h2 className="text-lg font-bold">Rechercher</h2>
                  <button
                    onClick={() => setMobileSearchOpen(false)}
                    className="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800"
                  >
                    <ArrowRight className="w-5 h-5 rotate-180" />
                  </button>
                </div>

                {/* Champs de recherche empilés */}
                <form onSubmit={(e) => { handleSubmit(e); setMobileSearchOpen(false); }} className="flex-1 overflow-y-auto p-4 space-y-4">
                  {/* Destination */}
                  <div>
                    <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Où allez-vous ?</label>
                    <SearchInputWithAutocomplete
                      value={city || search}
                      onChange={(value, type, extra) => {
                        setSearch(value);
                        if (type === 'city') setCity(value);
                        else if (type === 'accommodation' && extra?.city) setCity(extra.city);
                        else setCity(value);
                      }}
                      placeholder="Destination, ville, hébergement..."
                      showIcon={true}
                      inputClassName="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-base"
                    />
                  </div>

                  {/* Dates */}
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Arrivée</label>
                      <input
                        type="date"
                        value={checkIn}
                        onChange={(e) => {
                          setCheckIn(e.target.value);
                          if (checkOut && e.target.value && checkOut < e.target.value) {
                            setCheckOut('');
                          }
                        }}
                        min={today}
                        className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-sm"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Départ</label>
                      <input
                        type="date"
                        value={checkOut}
                        onChange={(e) => setCheckOut(e.target.value)}
                        min={minCheckOut}
                        disabled={!checkIn}
                        className={`w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-sm ${
                          !checkIn ? 'opacity-50' : ''
                        }`}
                      />
                    </div>
                  </div>

                  {/* Voyageurs */}
                  <div>
                    <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Voyageurs</label>
                    <div className="flex items-center gap-4 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800">
                      <button type="button" onClick={() => setGuests(Math.max(1, guests - 1))} className="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-lg font-bold">−</button>
                      <span className="flex-1 text-center text-base font-medium">{guests} {guests > 1 ? 'Voyageurs' : 'Voyageur'}</span>
                      <button type="button" onClick={() => setGuests(Math.min(20, guests + 1))} className="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-lg font-bold">+</button>
                    </div>
                  </div>

                  {/* Bouton rechercher */}
                  <button
                    type="submit"
                    className="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-4 rounded-xl transition-colors flex items-center justify-center gap-2 text-base"
                  >
                    <Search className="w-5 h-5" />
                    Rechercher
                  </button>
                </form>
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Barre de recherche desktop - cachée sur mobile */}
        <motion.form
          onSubmit={handleSubmit}
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, delay: 0.3 }}
          className="hidden md:block mx-5 bg-white rounded-lg shadow-2xl mb-6"
        >
            <div className="flex flex-row">
              {/* Où allez-vous - avec autocomplétion */}
              <div className="flex-1 px-4 py-4 border-r border-gray-200">
                <label className="block text-xs text-gray-500 mb-1 font-medium">Où allez-vous ?</label>
                <SearchInputWithAutocomplete
                  value={city || search}
                  onChange={(value, type, extra) => {
                    setSearch(value);
                    if (type === 'city') setCity(value);
                    else if (type === 'accommodation' && extra?.city) setCity(extra.city);
                    else setCity(value);
                  }}
                  placeholder="Destination, ville, hébergement..."
                  showIcon={false}
                  inputClassName="!border-0 !p-0 text-gray-900 placeholder-gray-400 focus:ring-0 focus:outline-none text-base"
                />
              </div>

              {/* Dates */}
              <div className="flex-1 px-4 py-4 border-r border-gray-200">
                <label className="block text-xs text-gray-500 mb-1 font-medium">Dates</label>
                <div className="flex items-center gap-2">
                  <input
                    type="date"
                    value={checkIn}
                    onChange={(e) => {
                      setCheckIn(e.target.value);
                      if (checkOut && e.target.value && checkOut < e.target.value) {
                        setCheckOut('');
                      }
                    }}
                    min={today}
                    className="flex-1 text-gray-900 text-sm focus:outline-none border-none p-0"
                  />
                  <span className="text-gray-400">-</span>
                  <input
                    type="date"
                    value={checkOut}
                    onChange={(e) => setCheckOut(e.target.value)}
                    min={minCheckOut}
                    disabled={!checkIn}
                    className={`flex-1 text-gray-900 text-sm focus:outline-none border-none p-0 ${
                      !checkIn ? 'opacity-50 cursor-not-allowed' : ''
                    }`}
                  />
                </div>
              </div>

              {/* Voyageurs */}
              <div className="flex-1 px-4 py-4 border-r border-gray-200">
                <label className="block text-xs text-gray-500 mb-1 font-medium">Voyageurs</label>
                <div className="flex items-center gap-2">
                  <input
                    type="number"
                    value={guests}
                    onChange={(e) => {
                      const value = parseInt(e.target.value) || 1;
                      setGuests(Math.max(1, value));
                    }}
                    min={1}
                    max={20}
                    className="w-12 text-gray-900 focus:outline-none border-none p-0 text-base font-medium"
                  />
                  <span className="text-gray-600 text-sm flex-1">
                    {guests} {guests > 1 ? 'Voyageurs' : 'Voyageur'}
                  </span>
                  <Users className="w-4 h-4 text-gray-400" />
                </div>
              </div>

              {/* Bouton Rechercher */}
              <button
                type="submit"
                className="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-10 py-4 transition-colors duration-200 flex items-center justify-center gap-2 whitespace-nowrap rounded-r-lg"
              >
                <Search className="w-5 h-5" />
                Rechercher
              </button>
            </div>
          </motion.form>

        {/* Contenu de gauche (fonctionnalités et CTA) */}
        <div className="container mx-auto px-4 md:px-8 lg:px-12 max-w-7xl">
          <div className="max-w-2xl">
            {/* Fonctionnalités */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.4 }}
              className="flex flex-wrap gap-6 md:gap-8 mb-8"
            >
              <div className="flex items-center gap-2 text-white">
                <Shield className="w-5 h-5 text-red-600 flex-shrink-0" />
                <span className="text-sm md:text-base font-medium">Hébergements contrôlés</span>
              </div>
              <div className="flex items-center gap-2 text-white">
                <Lock className="w-5 h-5 flex-shrink-0" />
                <span className="text-sm md:text-base font-medium">Paiement sécurisé</span>
              </div>
              <a
                href="https://wa.me/2250705654775?text=Bonjour%2C%20j%27ai%20besoin%20d%27assistance."
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2 text-white hover:text-green-300 transition-colors"
              >
                <Headphones className="w-5 h-5 flex-shrink-0" />
                <span className="text-sm md:text-base font-medium">Assistance locale 7j/7</span>
              </a>
            </motion.div>

            {/* Bouton CTA */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.5 }}
            >
              <Link
                href="/accommodations"
                className="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-700 text-white font-semibold px-6 py-3 rounded-lg transition-colors duration-200"
              >
                <span>Découvrir les meilleurs séjours</span>
                <ArrowRight className="w-5 h-5" />
              </Link>
            </motion.div>
          </div>
        </div>
      </div>
    </div>
  );
}
