import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { peopleHrApi } from '../api';

export const hrKeys = {
  teachers: (params?: any) => ['people-hr', 'teachers', params] as const,
};

export function useTeachers(params?: any) {
  return useQuery({
    queryKey: hrKeys.teachers(params),
    queryFn: () => peopleHrApi.teachers.list(params),
  });
}

export function useCreateTeacher() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: peopleHrApi.teachers.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['people-hr', 'teachers'] }),
  });
}

export function useTransferTeacher() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: any }) => peopleHrApi.teachers.transfer(id, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['people-hr', 'teachers'] }),
  });
}
