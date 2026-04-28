'use client';

import Image from 'next/image';
import Link from 'next/link';

interface LogoProps {
  href?: string;
  className?: string;
  size?: 'sm' | 'md' | 'lg';
  useImage?: boolean; // Option pour utiliser l'image ou le texte
}

export default function Logo({ href = '/', className = '', size = 'md', useImage = true }: LogoProps) {
  const sizeClasses = {
    sm: 'h-8',
    md: 'h-12',
    lg: 'h-16',
  };

  const logoContent = useImage ? (
    <Image
      src="/images/payment-methods/logo/logo.png"
      alt="Bosejour - Votre séjour commence ici..."
      width={size === 'sm' ? 120 : size === 'md' ? 180 : 240}
      height={size === 'sm' ? 40 : size === 'md' ? 60 : 80}
      className={`${sizeClasses[size]} w-auto object-contain ${className}`}
      priority
    />
  ) : (
    // Fallback au logo texte si l'image n'est pas disponible
    <span className={`font-logo font-bold flex items-center text-2xl ${className}`}>
      <span className="text-primary">B</span>
      <span className="text-accent">ose</span>
      <span className="text-accent">jour</span>
    </span>
  );

  if (href) {
    return (
      <Link href={href} className="flex items-center">
        {logoContent}
      </Link>
    );
  }

  return logoContent;
}


