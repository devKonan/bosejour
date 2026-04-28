'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import { Search, MapPin, Building2 } from 'lucide-react';
import api from '@/lib/api';

interface SuggestionCity {
  type: 'city';
  value: string;
  label: string;
}

interface SuggestionAccommodation {
  type: 'accommodation';
  id: number;
  value: string;
  label: string;
  city: string;
  slug: string;
}

type Suggestion = SuggestionCity | SuggestionAccommodation;

interface SearchInputWithAutocompleteProps {
  value: string;
  onChange: (value: string, type?: 'city' | 'accommodation', extra?: { city?: string; slug?: string }) => void;
  placeholder?: string;
  className?: string;
  inputClassName?: string;
  showIcon?: boolean;
}

export default function SearchInputWithAutocomplete({
  value,
  onChange,
  placeholder = 'Rechercher un hébergement, une ville...',
  className = '',
  inputClassName = '',
  showIcon = true,
}: SearchInputWithAutocompleteProps) {
  const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
  const [isOpen, setIsOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [highlightedIndex, setHighlightedIndex] = useState(-1);
  const containerRef = useRef<HTMLDivElement>(null);
  const debounceRef = useRef<NodeJS.Timeout | null>(null);

  const fetchSuggestions = useCallback(async (query: string) => {
    if (query.length < 2) {
      setSuggestions([]);
      return;
    }
    setLoading(true);
    try {
      const { data } = await api.get<{ cities: string[]; accommodations: { id: number; name: string; city: string; slug: string }[] }>(
        '/accommodations/suggestions',
        { params: { q: query, limit: 10 } }
      );
      const items: Suggestion[] = [
        ...data.cities.map((c) => ({ type: 'city' as const, value: c, label: c })),
        ...data.accommodations.map((a) => ({
          type: 'accommodation' as const,
          id: a.id,
          value: a.name,
          label: a.name,
          city: a.city,
          slug: a.slug,
        })),
      ];
      setSuggestions(items);
      setHighlightedIndex(-1);
    } catch {
      setSuggestions([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    if (value.length < 2) {
      setSuggestions([]);
      setIsOpen(false);
      return;
    }
    debounceRef.current = setTimeout(() => {
      fetchSuggestions(value);
      setIsOpen(true);
    }, 300);
    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
    };
  }, [value, fetchSuggestions]);

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleSelect = (s: Suggestion) => {
    if (s.type === 'city') {
      onChange(s.value, 'city', { city: s.value });
    } else {
      onChange(s.value, 'accommodation', { city: s.city, slug: s.slug });
    }
    setIsOpen(false);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (!isOpen || suggestions.length === 0) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setHighlightedIndex((i) => Math.min(i + 1, suggestions.length - 1));
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      setHighlightedIndex((i) => Math.max(i - 1, -1));
    } else if (e.key === 'Enter' && highlightedIndex >= 0 && suggestions[highlightedIndex]) {
      e.preventDefault();
      handleSelect(suggestions[highlightedIndex]);
    } else if (e.key === 'Escape') {
      setIsOpen(false);
      setHighlightedIndex(-1);
    }
  };

  return (
    <div ref={containerRef} className={`relative ${className}`}>
      <div className="relative">
        {showIcon && (
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5 pointer-events-none" />
        )}
        <input
          type="text"
          value={value}
          onChange={(e) => onChange(e.target.value)}
          onFocus={() => value.length >= 2 && suggestions.length > 0 && setIsOpen(true)}
          onKeyDown={handleKeyDown}
          placeholder={placeholder}
          autoComplete="off"
          className={`w-full border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white dark:bg-gray-800 transition-colors ${
            showIcon ? 'pl-10' : 'pl-4'
          } pr-4 py-3 ${inputClassName}`}
        />
      </div>

      {isOpen && (suggestions.length > 0 || loading) && (
        <div className="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-50 overflow-hidden">
          {loading ? (
            <div className="px-4 py-3 text-gray-500 dark:text-gray-400 text-sm">
              Chargement...
            </div>
          ) : (
            <ul className="py-1 max-h-60 overflow-y-auto">
              {suggestions.map((s, i) => (
                <li key={s.type + (s.type === 'city' ? s.value : s.id)}>
                  <button
                    type="button"
                    onClick={() => handleSelect(s)}
                    onMouseEnter={() => setHighlightedIndex(i)}
                    className={`w-full px-4 py-3 flex items-center gap-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors ${
                      highlightedIndex === i ? 'bg-gray-100 dark:bg-gray-700' : ''
                    }`}
                  >
                    {s.type === 'city' ? (
                      <MapPin className="w-4 h-4 text-primary flex-shrink-0" />
                    ) : (
                      <Building2 className="w-4 h-4 text-primary flex-shrink-0" />
                    )}
                    <div className="flex-1 min-w-0">
                      <span className="font-medium text-gray-900 dark:text-gray-100 truncate block">
                        {s.label}
                      </span>
                      {s.type === 'accommodation' && s.city && (
                        <span className="text-xs text-gray-500 dark:text-gray-400">{s.city}</span>
                      )}
                    </div>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  );
}
