/**
 * Academic Module - TanStack Query Hooks
 */

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { academicApi } from '../api';
import type { Student, AcademicClass, Session } from '../types';

export const academicKeys = {
  all: ['academic'] as const,
  students: (p?: any) => [...academicKeys.all, 'students', p] as const,
  student: (id: string) => [...academicKeys.students(), id] as const,
  classes: (p?: any) => [...academicKeys.all, 'classes', p] as const,
  class: (id: string) => [...academicKeys.classes(), id] as const,
  sessions: (classId: string) => [...academicKeys.all, 'sessions', classId] as const,
  programs: () => [...academicKeys.all, 'programs'] as const,
  programVersions: (programId: string) => [...academicKeys.all, 'programVersions', programId] as const,
};

export function useStudents(params?: any) {
  return useQuery({
    queryKey: academicKeys.students(params),
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
    queryKey: academicKeys.classes(params),
    queryFn: () => academicApi.classes.list(params),
  });
}

export function useClass(id: string) {
  return useQuery({
    queryKey: academicKeys.class(id),
    queryFn: () => academicApi.classes.get(id),
    enabled: !!id,
  });
}

export function useSessions(classId: string) {
  return useQuery({
    queryKey: academicKeys.sessions(classId),
    queryFn: () => academicApi.sessions.list(classId),
    enabled: !!classId,
  });
}

export function useCreateStudent() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: academicApi.students.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['academic', 'students'] }),
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
    onSuccess: () => qc.invalidateQueries({ queryKey: ['academic', 'classes'] }),
  });
}

export function useCreateSession() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ classId, data }: { classId: string; data: any }) =>
      academicApi.sessions.create(classId, data),
    onSuccess: (_, vars) => qc.invalidateQueries({ queryKey: academicKeys.sessions(vars.classId) }),
  });
}

export function useUpdateAttendance() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ sessionId, attendance }: { sessionId: string; attendance: any }) =>
      academicApi.sessions.updateAttendance(sessionId, attendance),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['academic', 'sessions'] });
    },
  });
}

export function useRoster(sessionId: string) {
  return useQuery({
    queryKey: [...academicKeys.all, 'roster', sessionId],
    queryFn: () => academicApi.sessions.roster(sessionId),
    enabled: !!sessionId,
  });
}

export function usePrograms() {
  return useQuery({
    queryKey: academicKeys.programs(),
    queryFn: () => academicApi.programs.list(),
  });
}

export function useProgramVersions(programId: string) {
  return useQuery({
    queryKey: academicKeys.programVersions(programId),
    queryFn: () => academicApi.programs.versions(programId),
    enabled: !!programId,
  });
}
