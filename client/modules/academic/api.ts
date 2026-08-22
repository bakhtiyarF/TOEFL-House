/**
 * Academic Module - API Client
 * Typed fetch calls for students, classes, sessions, enrollments
 */

import type { Student, AcademicClass, Session, Enrollment } from './types';

const API_BASE = '/api';

async function request<T>(url: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_BASE}${url}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...options.headers,
    },
    credentials: 'include',
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    throw new Error(error.message || 'Request failed');
  }
  if (response.status === 204) return {} as T;
  return response.json();
}

export const academicApi = {
  // Students
  students: {
    list: (params?: { search?: string; status?: string; branch_id?: string; per_page?: number }) => {
      const q = new URLSearchParams(params as any).toString();
      return request<Student[]>(`/students?${q}`);
    },
    get: (id: string) => request<Student>(`/students/${id}`),
    create: (data: Partial<Student>) => request<Student>('/students', { method: 'POST', body: JSON.stringify(data) }),
    update: (id: string, data: Partial<Student>) => request<Student>(`/students/${id}`, { method: 'PATCH', body: JSON.stringify(data) }),
    delete: (id: string) => request<void>(`/students/${id}`, { method: 'DELETE' }),
    journey: (id: string) => request<any[]>(`/students/${id}/journey`),
    enroll: (id: string, data: any) => request<any>(`/students/${id}/enroll`, { method: 'POST', body: JSON.stringify(data) }),
  },

  // Classes
  classes: {
    list: (params?: { status?: string; search?: string; branch_id?: string }) => {
      const q = new URLSearchParams(params as any).toString();
      return request<AcademicClass[]>(`/classes?${q}`);
    },
    get: (id: string) => request<any>(`/classes/${id}`),
    create: (data: Partial<AcademicClass>) => request<AcademicClass>('/classes', { method: 'POST', body: JSON.stringify(data) }),
    update: (id: string, data: Partial<AcademicClass>) => request<AcademicClass>(`/classes/${id}`, { method: 'PATCH', body: JSON.stringify(data) }),
  },

  // Sessions
  sessions: {
    list: (classId: string) => request<Session[]>(`/classes/${classId}/sessions`),
    create: (classId: string, data: any) => request<Session>(`/classes/${classId}/sessions`, { method: 'POST', body: JSON.stringify(data) }),
    roster: (sessionId: string) => request<any>(`/sessions/${sessionId}/roster`),
    updateAttendance: (sessionId: string, data: Record<string, string>) =>
      request<void>(`/sessions/${sessionId}/attendance`, { method: 'POST', body: JSON.stringify({ attendance: data }) }),
  },

  // Programs / Catalog (for enrollment copy-on-write, programs list & versions)
  programs: {
    list: () => request<any[]>('/programs'),
    versions: (programId: string) => request<any[]>(`/programs/${programId}/versions`),
  },
};
