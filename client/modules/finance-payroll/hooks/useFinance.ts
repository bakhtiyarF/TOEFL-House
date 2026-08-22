import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { financeApi } from '../api';

export const financeKeys = {
  payments: (p?: any) => ['finance', 'payments', p] as const,
  studentFinance: (id: string) => ['finance', 'student', id] as const,
  teacherSalary: (id: string) => ['finance', 'teacher', id] as const,
};

export function usePayments(params?: any) {
  return useQuery({ queryKey: financeKeys.payments(params), queryFn: () => financeApi.payments.list(params) });
}

export function useStudentFinance(studentId: string) {
  return useQuery({ queryKey: financeKeys.studentFinance(studentId), queryFn: () => financeApi.studentFinance(studentId), enabled: !!studentId });
}

export function useCreatePayment() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: financeApi.payments.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['finance'] }),
  });
}

export function useTeacherSalary(teacherId: string, period?: string) {
  return useQuery({
    queryKey: financeKeys.teacherSalary(teacherId),
    queryFn: () => financeApi.teacherSalary(teacherId, period),
    enabled: !!teacherId,
  });
}
