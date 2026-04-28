'use client';

import { useState, useEffect, useCallback } from 'react';
import Image from 'next/image';
import { motion, AnimatePresence } from 'framer-motion';
import { ChevronLeft, ChevronRight, Play, Maximize2 } from 'lucide-react';
import { FadeIn } from '@/components/common/animations';
import ImageLightbox from '@/components/common/ImageLightbox';

interface MediaItem {
  url: string;
}

interface MediaCarouselProps {
  items: MediaItem[];
  autoPlay?: boolean;
  autoPlayInterval?: number;
}

export default function MediaCarousel({ 
  items, 
  autoPlay = true,
  autoPlayInterval = 5000 
}: MediaCarouselProps) {
  const [current, setCurrent] = useState(0);
  const [direction, setDirection] = useState(0);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const [isHovered, setIsHovered] = useState(false);
  const total = items.length;

  // Filtrer seulement les images (pas les vidéos) pour le lightbox
  const imagesOnly = items.filter(item => !/\.(mp4|mov|avi)$/i.test(item.url));

  const isVideo = (url: string) => /\.(mp4|mov|avi)$/i.test(url);

  const go = useCallback((dir: number) => {
    setDirection(dir);
    setCurrent((prev) => (prev + dir + total) % total);
  }, [total]);

  const goTo = useCallback((index: number) => {
    setCurrent((prev) => {
      setDirection(index > prev ? 1 : -1);
      return index;
    });
  }, []);

  // Auto-play functionality
  useEffect(() => {
    if (!autoPlay || total <= 1 || lightboxOpen || isHovered) return;
    
    const interval = setInterval(() => {
      go(1);
    }, autoPlayInterval);

    return () => clearInterval(interval);
  }, [autoPlay, autoPlayInterval, total, lightboxOpen, isHovered, go]);

  const [lightboxImageIndex, setLightboxImageIndex] = useState(0);

  const handleImageClick = () => {
    if (!isVideo(items[current].url) && imagesOnly.length > 0) {
      // Trouver l'index de l'image actuelle dans imagesOnly
      const imageIndex = imagesOnly.findIndex(img => img.url === items[current].url);
      if (imageIndex !== -1) {
        setLightboxImageIndex(imageIndex);
        setLightboxOpen(true);
      }
    }
  };

  // Synchroniser lightboxImageIndex avec current quand le carrousel change
  useEffect(() => {
    if (lightboxOpen && !isVideo(items[current].url)) {
      const imageIndex = imagesOnly.findIndex(img => img.url === items[current].url);
      if (imageIndex !== -1) {
        setLightboxImageIndex(imageIndex);
      }
    }
  }, [current, lightboxOpen, items, imagesOnly]);

  const handleLightboxNext = () => {
    const nextIndex = (lightboxImageIndex + 1) % imagesOnly.length;
    setLightboxImageIndex(nextIndex);
    // Mettre à jour le carrousel principal aussi
    const nextImage = imagesOnly[nextIndex];
    const nextItemIndex = items.findIndex(item => item.url === nextImage.url);
    if (nextItemIndex !== -1) {
      goTo(nextItemIndex);
    }
  };

  const handleLightboxPrevious = () => {
    const prevIndex = (lightboxImageIndex - 1 + imagesOnly.length) % imagesOnly.length;
    setLightboxImageIndex(prevIndex);
    // Mettre à jour le carrousel principal aussi
    const prevImage = imagesOnly[prevIndex];
    const prevItemIndex = items.findIndex(item => item.url === prevImage.url);
    if (prevItemIndex !== -1) {
      goTo(prevItemIndex);
    }
  };

  if (total === 0) return null;

  const slideVariants = {
    enter: (direction: number) => ({
      x: direction > 0 ? 1000 : -1000,
      opacity: 0,
    }),
    center: {
      zIndex: 1,
      x: 0,
      opacity: 1,
    },
    exit: (direction: number) => ({
      zIndex: 0,
      x: direction < 0 ? 1000 : -1000,
      opacity: 0,
    }),
  };

  return (
    <FadeIn>
      <div 
        className="relative group"
        onMouseEnter={() => setIsHovered(true)}
        onMouseLeave={() => setIsHovered(false)}
      >
        <div className="relative h-96 md:h-[500px] rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-900 shadow-xl">
          <AnimatePresence initial={false} custom={direction}>
            <motion.div
              key={current}
              custom={direction}
              variants={slideVariants}
              initial="enter"
              animate="center"
              exit="exit"
              transition={{
                x: { type: 'spring', stiffness: 300, damping: 30 },
                opacity: { duration: 0.2 },
              }}
              className="absolute inset-0"
            >
              {isVideo(items[current].url) ? (
                <div className="relative w-full h-full">
                  <video 
                    src={items[current].url} 
                    className="w-full h-full object-cover" 
                    controls
                    autoPlay
                    loop
                  />
                  <div className="absolute top-4 left-4 bg-black/70 text-white px-3 py-1 rounded-full text-xs flex items-center gap-2">
                    <Play className="w-3 h-3" />
                    Vidéo
                  </div>
                </div>
              ) : (
                <motion.div
                  className="relative w-full h-full cursor-pointer"
                  onClick={handleImageClick}
                  whileHover={{ scale: 1.02 }}
                  transition={{ duration: 0.3 }}
                >
                  <Image 
                    src={items[current].url} 
                    alt={`media-${current}`} 
                    fill 
                    className="object-cover"
                    priority={current === 0}
                  />
                  {/* Badge pour indiquer qu'on peut cliquer */}
                  <div className="absolute top-4 right-4 bg-black/60 hover:bg-black/80 backdrop-blur-sm rounded-full p-2 text-white transition-colors opacity-0 group-hover:opacity-100">
                    <Maximize2 className="w-4 h-4" />
                  </div>
                </motion.div>
              )}
            </motion.div>
          </AnimatePresence>

          {/* Indicateur de position */}
          {total > 1 && (
            <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-10">
              <div className="flex items-center gap-2 bg-black/50 backdrop-blur-sm px-4 py-2 rounded-full">
                <span className="text-white text-sm font-medium">
                  {current + 1} / {total}
                </span>
              </div>
            </div>
          )}

          {/* Boutons de navigation */}
          {total > 1 && (
            <>
              <motion.button
                onClick={() => go(-1)}
                className="absolute left-4 top-1/2 -translate-y-1/2 z-10 bg-white/90 dark:bg-black/70 hover:bg-white dark:hover:bg-black/90 backdrop-blur-sm rounded-full p-3 shadow-lg transition-all duration-200 opacity-0 group-hover:opacity-100"
                aria-label="Précédent"
                whileHover={{ scale: 1.1 }}
                whileTap={{ scale: 0.9 }}
              >
                <ChevronLeft className="w-6 h-6 text-gray-900 dark:text-white" />
              </motion.button>
              <motion.button
                onClick={() => go(1)}
                className="absolute right-4 top-1/2 -translate-y-1/2 z-10 bg-white/90 dark:bg-black/70 hover:bg-white dark:hover:bg-black/90 backdrop-blur-sm rounded-full p-3 shadow-lg transition-all duration-200 opacity-0 group-hover:opacity-100"
                aria-label="Suivant"
                whileHover={{ scale: 1.1 }}
                whileTap={{ scale: 0.9 }}
              >
                <ChevronRight className="w-6 h-6 text-gray-900 dark:text-white" />
              </motion.button>
            </>
          )}
        </div>

        {/* Miniatures */}
        {total > 1 && (
          <motion.div 
            className="mt-4 grid grid-cols-6 md:grid-cols-8 gap-2"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
          >
            {items.map((m, i) => (
              <motion.button
                key={i}
                onClick={() => goTo(i)}
                className={`relative h-16 md:h-20 rounded-md overflow-hidden ring-2 transition-all duration-200 ${
                  i === current 
                    ? 'ring-primary scale-105 shadow-lg' 
                    : 'ring-transparent hover:ring-primary/50 hover:scale-102'
                }`}
                aria-label={`Media ${i + 1}`}
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
              >
                {isVideo(m.url) ? (
                  <div className="w-full h-full bg-black/20 dark:bg-black/40 flex items-center justify-center text-xs relative">
                    <Play className="w-4 h-4 text-white" />
                    <div className="absolute top-1 right-1 bg-black/70 text-white text-[10px] px-1 rounded">
                      VID
                    </div>
                  </div>
                ) : (
                  <>
                    <Image 
                      src={m.url} 
                      alt={`thumb-${i}`} 
                      fill 
                      className="object-cover"
                    />
                    {i === current && (
                      <motion.div
                        className="absolute inset-0 bg-primary/20"
                        layoutId="activeThumb"
                        transition={{ type: 'spring', stiffness: 300, damping: 30 }}
                      />
                    )}
                  </>
                )}
              </motion.button>
            ))}
          </motion.div>
        )}

        {/* Lightbox */}
        {imagesOnly.length > 0 && (
          <ImageLightbox
            images={imagesOnly.map(img => ({ url: img.url }))}
            currentIndex={lightboxImageIndex}
            isOpen={lightboxOpen}
            onClose={() => setLightboxOpen(false)}
            onNext={handleLightboxNext}
            onPrevious={handleLightboxPrevious}
            onGoToIndex={(index) => {
              setLightboxImageIndex(index);
              const targetImage = imagesOnly[index];
              const targetItemIndex = items.findIndex(item => item.url === targetImage.url);
              if (targetItemIndex !== -1) {
                goTo(targetItemIndex);
              }
            }}
          />
        )}
      </div>
    </FadeIn>
  );
}


