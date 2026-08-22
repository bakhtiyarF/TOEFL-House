import { useQuery, useMutation } from '@tanstack/react-query';
import { platformApi } from '../api';

export function useGlobalSearch(query: string, type = 'all') {
  return useQuery({
    queryKey: ['platform', 'search', query, type],
    queryFn: () => platformApi.search(query, type),
    enabled: query.length > 1,
    staleTime: 10_000,
  });
}

export function useNotifications() {
  return useQuery({
    queryKey: ['platform', 'notifications'],
    queryFn: () => platformApi.notifications.list(),
  });
}

export function useMarkNotificationRead() {
  return useMutation({
    mutationFn: (id: string) => platformApi.notifications.markRead(id),
  });
}
