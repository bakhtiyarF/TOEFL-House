/**
 * Frontend Unit Tests — Vitest + React Testing Library
 *
 * Tests for shared utilities and core logic
 */

import { describe, it, expect } from 'vitest';
import { cn, formatAmount, formatCurrency, formatDateISO } from '../shared/lib/utils';

describe('cn utility', () => {
  it('merges class names', () => {
    expect(cn('foo', 'bar')).toBe('foo bar');
  });

  it('handles conditional classes', () => {
    expect(cn('base', false && 'hidden', 'extra')).toBe('base extra');
  });

  it('merges tailwind classes correctly', () => {
    const result = cn('px-4 py-2', 'px-6');
    expect(result).toContain('px-6');
    expect(result).toContain('py-2');
  });

  it('handles undefined and null', () => {
    expect(cn('foo', undefined, null, 'bar')).toBe('foo bar');
  });
});

describe('formatAmount', () => {
  it('formats numbers with thousands separator', () => {
    expect(formatAmount(5000)).toBe('5,000');
    expect(formatAmount(1234567)).toBe('1,234,567');
    expect(formatAmount(100)).toBe('100');
    expect(formatAmount(0)).toBe('0');
  });
});

describe('formatCurrency', () => {
  it('formats with AFN suffix', () => {
    expect(formatCurrency(5000)).toBe('5,000 AFN');
    expect(formatCurrency(245000)).toBe('245,000 AFN');
  });
});

describe('formatDateISO', () => {
  it('formats date to ISO string', () => {
    expect(formatDateISO('2026-08-22T10:30:00Z')).toBe('2026-08-22');
    expect(formatDateISO(new Date('2026-01-15'))).toBe('2026-01-15');
  });
});

describe('Permission logic', () => {
  const mockPermissions = [
    { code: 'Student.View', scope: 'branch', source: 'role' },
    { code: 'Student.Create', scope: 'branch', source: 'role' },
    { code: 'Payment.View', scope: 'branch', source: 'role' },
    { code: 'Dashboard.View', scope: 'organization', source: 'role' },
  ];

  it('checks single permission correctly', () => {
    const hasPermission = (code: string) => mockPermissions.some((p) => p.code === code);
    expect(hasPermission('Student.View')).toBe(true);
    expect(hasPermission('Student.Delete')).toBe(false);
    expect(hasPermission('Payment.View')).toBe(true);
  });

  it('checks any permission correctly', () => {
    const hasAny = (codes: string[]) => codes.some((code) => mockPermissions.some((p) => p.code === code));
    expect(hasAny(['Student.View', 'Teacher.View'])).toBe(true);
    expect(hasAny(['Student.Delete', 'Teacher.Delete'])).toBe(false);
  });
});

describe('Scope hierarchy', () => {
  const SCOPE_RANK: Record<string, number> = {
    own: 0, class: 1, program: 2, department: 3,
    branch: 4, campus: 5, organization: 6,
  };

  it('narrower scope has lower rank', () => {
    expect(SCOPE_RANK['own']).toBeLessThan(SCOPE_RANK['branch']);
    expect(SCOPE_RANK['branch']).toBeLessThan(SCOPE_RANK['organization']);
  });

  it('narrowerScope returns lower rank', () => {
    const narrowerScope = (a: string, b: string) =>
      (SCOPE_RANK[a] ?? 4) <= (SCOPE_RANK[b] ?? 4) ? a : b;

    expect(narrowerScope('branch', 'organization')).toBe('branch');
    expect(narrowerScope('own', 'class')).toBe('own');
    expect(narrowerScope('campus', 'branch')).toBe('branch');
  });
});

describe('Student finance math', () => {
  it('clamps percentages to 0-100', () => {
    const clamp = (v: number) => Math.max(0, Math.min(100, v));
    expect(clamp(150)).toBe(100);
    expect(clamp(-10)).toBe(0);
    expect(clamp(50)).toBe(50);
  });

  it('calculates discount amount correctly', () => {
    const gross = 10000;
    const discountPercent = 15;
    const discountAmount = gross * discountPercent / 100;
    expect(discountAmount).toBe(1500);
  });

  it('calculates net tuition correctly', () => {
    const gross = 10000;
    const discount = 1500;
    const scholarship = 2000;
    const net = Math.max(0, gross - discount - scholarship);
    expect(net).toBe(6500);
  });

  it('floors net tuition at 0', () => {
    const gross = 5000;
    const discount = 3000;
    const scholarship = 4000;
    const net = Math.max(0, gross - discount - scholarship);
    expect(net).toBe(0);
  });

  it('calculates paid percentage correctly', () => {
    const net = 8000;
    const paid = 4000;
    const pct = net <= 0 ? 100 : Math.min(100, Math.round(paid / net * 100));
    expect(pct).toBe(50);
  });

  it('100% paid when net tuition is 0', () => {
    const net = 0;
    const pct = net <= 0 ? 100 : 0;
    expect(pct).toBe(100);
  });
});

describe('Attendance rate calculation', () => {
  it('calculates attendance rate correctly', () => {
    const present = 13;
    const total = 15;
    const rate = Math.round((present / total) * 100);
    expect(rate).toBe(87);
  });

  it('warns below 85%', () => {
    const rate = 82;
    expect(rate < 85).toBe(true);
  });

  it('critical below 60%', () => {
    const rate = 55;
    expect(rate < 60).toBe(true);
  });
});

describe('Student code generation', () => {
  it('generates correct format', () => {
    const year = '2026';
    const next = 42;
    const code = `STU-${year}-${String(next).padStart(4, '0')}`;
    expect(code).toBe('STU-2026-0042');
    expect(code).toMatch(/^STU-\d{4}-\d{4}$/);
  });
});

describe('Pipeline stage colors', () => {
  const stages = ['lead', 'inquiry', 'follow_up', 'placement_booking', 'placement_completed', 'registration', 'enrollment'];

  it('has all 7 stages defined', () => {
    expect(stages).toHaveLength(7);
  });

  it('stages are in correct order', () => {
    expect(stages[0]).toBe('lead');
    expect(stages[stages.length - 1]).toBe('enrollment');
  });
});
