/**
 * Application Entry Point
 * Thin shell: routing + auth gate + providers only
 */

import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Toaster } from 'sonner';
import { AppRoutes } from './routes';

// TanStack Query client - one instance, shared globally
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60 * 1000, // 1 minute
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <AppRoutes />
        <Toaster
          position="top-right"
          toastOptions={{
            style: {
              direction: 'ltr', // Toasts stay LTR even in RTL layout
            },
          }}
        />
      </BrowserRouter>
    </QueryClientProvider>
  );
}
