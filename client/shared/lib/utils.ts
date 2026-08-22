import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * Format a number with thousands separators (AFN convention)
 * e.g. 5000 → "5,000"
 */
export function formatAmount(value: number): string {
  return new Intl.NumberFormat('en-AF').format(value);
}

/**
 * Format currency with AFN symbol
 */
export function formatCurrency(value: number): string {
  return `${formatAmount(value)} AFN`;
}

/**
 * Format a date as Gregorian ISO (YYYY-MM-DD)
 */
export function formatDateISO(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date;
  return d.toISOString().split('T')[0];
}
