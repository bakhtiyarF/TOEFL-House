import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

// @ts-ignore
const dirname = import.meta.dirname ?? path.dirname(new URL(import.meta.url).pathname);

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['tests/**/*.test.{ts,tsx}'],
  },
  resolve: {
    alias: {
      '@': path.resolve(dirname, './src'),
      '@modules': path.resolve(dirname, './modules'),
      '@shared': path.resolve(dirname, './shared'),
      '@app': path.resolve(dirname, './app'),
      '@reporting': path.resolve(dirname, './reporting'),
    },
  },
});
