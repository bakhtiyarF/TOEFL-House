/**
 * Mock Auth Store — for development preview
 * Simulates authentication without a running backend
 */

import { create } from 'zustand';
import type { AuthUser } from '@modules/iam/types';

interface MockAuthState {
  user: AuthUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (username: string, password: string) => Promise<boolean>;
  logout: () => void;
  hasPermission: (code: string) => boolean;
}

// Mock user for preview purposes
const mockUser: AuthUser = {
  id: 'mock-user-1',
  username: 'admin',
  full_name: 'Ahmad Rahimi',
  role: 'manager',
  branch_id: 'branch-1',
  is_active: true,
  two_factor_enabled: false,
  must_change_password: false,
  created_at: '2026-01-01T00:00:00Z',
  permissions: [
    { code: 'Dashboard.View', scope: 'branch', source: 'role' },
    { code: 'Dashboard.Executive', scope: 'branch', source: 'role' },
    { code: 'Student.View', scope: 'branch', source: 'role' },
    { code: 'Student.Create', scope: 'branch', source: 'role' },
    { code: 'Student.Edit', scope: 'branch', source: 'role' },
    { code: 'Class.View', scope: 'branch', source: 'role' },
    { code: 'Class.Create', scope: 'branch', source: 'role' },
    { code: 'Class.Edit', scope: 'branch', source: 'role' },
    { code: 'Teacher.View', scope: 'branch', source: 'role' },
    { code: 'Teacher.Create', scope: 'branch', source: 'role' },
    { code: 'Payment.View', scope: 'branch', source: 'role' },
    { code: 'Payment.Create', scope: 'branch', source: 'role' },
    { code: 'Book.View', scope: 'branch', source: 'role' },
    { code: 'Book.Sell', scope: 'branch', source: 'role' },
    { code: 'Lead.View', scope: 'branch', source: 'role' },
    { code: 'Lead.Create', scope: 'branch', source: 'role' },
    { code: 'Lead.Convert', scope: 'branch', source: 'role' },
    { code: 'Funding.View', scope: 'branch', source: 'role' },
    { code: 'Settings.View', scope: 'branch', source: 'role' },
    { code: 'Session.View', scope: 'branch', source: 'role' },
    { code: 'Session.Edit', scope: 'branch', source: 'role' },
    { code: 'Attendance.View', scope: 'branch', source: 'role' },
    { code: 'Attendance.Edit', scope: 'branch', source: 'role' },
    { code: 'Exam.View', scope: 'branch', source: 'role' },
    { code: 'Grade.View', scope: 'branch', source: 'role' },
    { code: 'Budget.Allocate', scope: 'branch', source: 'role' },
    { code: 'User.View', scope: 'branch', source: 'role' },
  ],
  branch: {
    id: 'branch-1',
    name: 'Main Campus - Kabul',
    location: 'Kabul, Afghanistan',
    is_active: true,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
};

export const useMockAuth = create<MockAuthState>()((set, get) => ({
  user: null,
  isAuthenticated: false,
  isLoading: false,

  login: async (username: string, _password: string) => {
    set({ isLoading: true });
    // Simulate network delay
    await new Promise((r) => setTimeout(r, 800));

    if (username && _password) {
      set({ user: mockUser, isAuthenticated: true, isLoading: false });
      return true;
    }

    set({ isLoading: false });
    return false;
  },

  logout: () => {
    set({ user: null, isAuthenticated: false });
  },

  hasPermission: (code: string) => {
    const user = get().user;
    if (!user?.permissions) return false;
    return user.permissions.some((p) => p.code === code);
  },
}));
