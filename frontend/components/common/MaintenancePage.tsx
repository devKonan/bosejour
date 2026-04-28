'use client';

export default function MaintenancePage({ message }: { message: string }) {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 px-4 text-center">
      <div className="text-7xl mb-6">🔧</div>
      <h1 className="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-4">
        Maintenance en cours
      </h1>
      <p className="text-gray-600 dark:text-gray-400 max-w-md text-base leading-relaxed">
        {message || 'La plateforme est momentanément en maintenance. Merci de revenir plus tard.'}
      </p>
    </div>
  );
}
