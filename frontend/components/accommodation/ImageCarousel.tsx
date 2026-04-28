'use client';

import { useState, useEffect } from 'react';
import Image from 'next/image';
import { motion, AnimatePresence } from 'framer-motion';
import { ChevronLeft, ChevronRight, Maximize2 } from 'lucide-react';
import ImageLightbox from '@/components/common/ImageLightbox';

interface ImageCarouselProps {
  images: Array<{ url: string; is_primary?: boolean }>;
  className?: string;
  autoPlay?: boolean;
  autoPlayInterval?: number;
}

export default function ImageCarousel({ 
  images, 
  className = '',
  autoPlay = true,
  autoPlayInterval = 4000
}: ImageCarouselProps) {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [direction, setDirection] = useState(0);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const [isHovered, setIsHovered] = useState(false);

  if (!images || images.length === 0) return null;

  // Auto-play functionality
  useEffect(() => {
    if (!autoPlay || images.length <= 1 || lightboxOpen || isHovered) return;
    
    const interval = setInterval(() => {
      setCurrentIndex((prev) => (prev + 1) % images.length);
      setDirection(1);
    }, autoPlayInterval);

    return () => clearInterval(interval);
  }, [autoPlay, autoPlayInterval, images.length, lightboxOpen, isHovered]);

  const goToSlide = (index: number) => {
    setDirection(index > currentIndex ? 1 : -1);
    setCurrentIndex(index);
  };

  const goPrevious = () => {
    const newIndex = currentIndex === 0 ? images.length - 1 : currentIndex - 1;
    setDirection(-1);
    setCurrentIndex(newIndex);
  };

  const goNext = () => {
    const newIndex = currentIndex === images.length - 1 ? 0 : currentIndex + 1;
    setDirection(1);
    setCurrentIndex(newIndex);
  };

  const slideVariants = {
    enter: (direction: number) => ({
      x: direction > 0 ? 300 : -300,
      opacity: 0,
    }),
    center: {
      x: 0,
      opacity: 1,
    },
    exit: (direction: number) => ({
      x: direction < 0 ? 300 : -300,
      opacity: 0,
    }),
  };

  return (
    <div 
      className={`relative group ${className}`}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      <div className="relative h-full w-full overflow-hidden rounded-lg">
        <AnimatePresence initial={false} custom={direction}>
          <motion.div
            key={currentIndex}
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
            <motion.div
              className="relative w-full h-full cursor-pointer"
              onClick={() => setLightboxOpen(true)}
              whileHover={{ scale: 1.05 }}
              transition={{ duration: 0.3 }}
            >
              <Image
                src={images[currentIndex].url}
                alt={`Image ${currentIndex + 1}`}
                fill
                className="object-cover"
              />
              {/* Badge pour indiquer qu'on peut cliquer */}
              <div className="absolute top-2 left-2 bg-black/60 hover:bg-black/80 backdrop-blur-sm rounded-full p-1.5 text-white transition-colors opacity-0 group-hover:opacity-100">
                <Maximize2 className="w-3 h-3" />
              </div>
            </motion.div>
          </motion.div>
        </AnimatePresence>

        {/* Indicateurs de points */}
        {images.length > 1 && (
          <div className="absolute bottom-2 left-1/2 -translate-x-1/2 z-10 flex gap-1.5">
            {images.map((_, index) => (
              <button
                key={index}
                onClick={() => goToSlide(index)}
                className={`h-1.5 rounded-full transition-all duration-300 ${
                  index === currentIndex
                    ? 'w-6 bg-white'
                    : 'w-1.5 bg-white/50 hover:bg-white/75'
                }`}
                aria-label={`Aller à l'image ${index + 1}`}
              />
            ))}
          </div>
        )}

        {/* Boutons de navigation */}
        {images.length > 1 && (
          <>
            <motion.button
              onClick={goPrevious}
              className="absolute left-2 top-1/2 -translate-y-1/2 z-10 bg-black/40 hover:bg-black/60 backdrop-blur-sm rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
              aria-label="Image précédente"
              whileHover={{ scale: 1.1 }}
              whileTap={{ scale: 0.9 }}
            >
              <ChevronLeft className="w-4 h-4 text-white" />
            </motion.button>
            <motion.button
              onClick={goNext}
              className="absolute right-2 top-1/2 -translate-y-1/2 z-10 bg-black/40 hover:bg-black/60 backdrop-blur-sm rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
              aria-label="Image suivante"
              whileHover={{ scale: 1.1 }}
              whileTap={{ scale: 0.9 }}
            >
              <ChevronRight className="w-4 h-4 text-white" />
            </motion.button>
          </>
        )}

        {/* Badge nombre d'images */}
        {images.length > 1 && (
          <div className="absolute top-2 right-2 z-10 bg-black/60 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full">
            {currentIndex + 1} / {images.length}
          </div>
        )}
      </div>

      {/* Lightbox */}
      <ImageLightbox
        images={images}
        currentIndex={currentIndex}
        isOpen={lightboxOpen}
        onClose={() => setLightboxOpen(false)}
        onNext={() => {
          const nextIndex = (currentIndex + 1) % images.length;
          setCurrentIndex(nextIndex);
          setDirection(1);
        }}
        onPrevious={() => {
          const prevIndex = (currentIndex - 1 + images.length) % images.length;
          setCurrentIndex(prevIndex);
          setDirection(-1);
        }}
        onGoToIndex={(index) => {
          setCurrentIndex(index);
          setDirection(index > currentIndex ? 1 : -1);
        }}
      />
    </div>
  );
}

