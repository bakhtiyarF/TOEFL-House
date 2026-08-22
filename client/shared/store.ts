/**
 * Shared UI Store — Zustand
 * Only for state that isn't server data (01 §4)
 * Sidebar state, table density, dark mode
 */

import { create } from 'zustand';
import { persist } from 'zustand/middleware';

type TableDensity = 'comfortable' | 'compact' | 'dense';

interface UIState {
  sidebarOpen: boolean;
  tableDensity: TableDensity;
  darkMode: boolean;
  setSidebarOpen: (open: boolean) => void;
  toggleSidebar: () => void;
  setTableDensity: (density: TableDensity) => void;
  toggleDarkMode: () => void;
}

export const useUIStore = create<UIState>()(
  persist(
    (set) => ({
      sidebarOpen: true,
      tableDensity: 'comfortable',
      darkMode: false,

      setSidebarOpen: (open) => set({ sidebarOpen: open }),
      toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
      setTableDensity: (density) => set({ tableDensity: density }),
      toggleDarkMode: () =>
        set((state) => {
          const newMode = !state.darkMode;
          document.documentElement.classList.toggle('dark', newMode);
          return { darkMode: newMode };
        }),
    }),
    {
      name: 'toefl-house-ui',
    }
  )
);
