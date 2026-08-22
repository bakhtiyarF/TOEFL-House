/**
 * Academic Module - TanStack Query Hooks
 */

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { academicApi } from '../api';
import type { Student, AcademicClass } from '../types';

export const academicKeys = {
  all: ['academic'] as const,
  students: () => [...academicKeys.all, 'students'] as const,
  student: (id: string) => [...academicKeys.students(), id] as const,
  classes: () => [...academicKeys.all, 'classes'] as const,
  class: (id: string) => [...academicKeys.classes(), id] as const,
};

export function useStudents(params?: any) {
  return useQuery({
    queryKey: [...academicKeys.students(), params],
    queryFn: () => academicApi.students.list(params),
    staleTime: 30_000,
  });
}

export function useStudent(id: string) {
  return useQuery({
    queryKey: academicKeys.student(id),
    queryFn: () => academicApi.students.get(id),
    enabled: !!id,
  });
}

export function useClasses(params?: any) {
  return useQuery({
    queryKey: [...academicKeys.classes(), params],
    queryFn: () => academicApi.classes.list(params),
  });
}

export function useCreateStudent() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: academicApi.students.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: academicKeys.students() }),
  });
}

export function useEnrollStudent() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ studentId, data }: { studentId: string; data: any }) =>
      academicApi.students.enroll(studentId, data),
    onSuccess: (_, vars) => {
      qc.invalidateQueries({ queryKey: academicKeys.student(vars.studentId) });
    },
  });
}

export function useCreateClass() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: academicApi.classes.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: academicKeys.classes() }),
  });
}
