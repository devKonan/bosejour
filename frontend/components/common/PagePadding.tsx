'use client';

import { usePathname } from 'next/navigation';

export default function PagePadding({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const isHome = pathname === '/';
  return (
    <div className={`pb-20 md:pb-0 ${isHome ? '' : 'pt-20'}`}>
      {children}
    </div>
  );
}
