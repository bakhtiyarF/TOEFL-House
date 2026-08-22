import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { crmApi } from '../api';

export function useVisitors(params?: any) {
  return useQuery({ queryKey: ['crm', 'visitors', params], queryFn: () => crmApi.visitors.list(params) });
}

export function useCreateVisitor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: crmApi.visitors.create,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['crm', 'visitors'] });
    },
  });
}

export function useConvertVisitor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: string; data?: any }) => crmApi.visitors.convert(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['crm'] });
      qc.invalidateQueries({ queryKey: ['academic', 'students'] });
    },
  });
}
