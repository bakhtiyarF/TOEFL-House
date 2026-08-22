/**
 * Finance & Payroll Module - API Client
 */
const API_BASE = '/api';

async function request<T>(url: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(`${API_BASE}${url}`, {
    ...options,
    headers: { 'Content-Type': 'application/json', ...options.headers },
    credentials: 'include',
  });
  if (!res.ok) throw new Error(await res.text());
  return res.status === 204 ? ({} as T) : res.json();
}

export const financeApi = {
  payments: {
    list: (params?: any) => request<any[]>(`/payments?${new URLSearchParams(params || {}).toString()}`),
    create: (data: any) => request('/payments', { method: 'POST', body: JSON.stringify(data) }),
  },
  studentFinance: (studentId: string) => request(`/students/${studentId}/finance-summary`),
  teacherSalary: (teacherId: string, period?: string) =>
    request(`/teachers/${teacherId}/computed-salary${period ? `?period=${period}` : ''}`),
  payTeacher: (teacherId: string, data: any) =>
    request(`/teachers/${teacherId}/pay-salary`, { method: 'POST', body: JSON.stringify(data) }),
  budgetLines: {
    list: (params?: any) => {
      const q = new URLSearchParams(params || {}).toString();
      return request<any[]>(`/budget-lines?${q}`);
    },
    create: (d: any) => request('/budget-lines', { method: 'POST', body: JSON.stringify(d) }),
  },
  // Payroll processing entry
  processPayroll: (data: any) => request('/payroll/process', { method: 'POST', body: JSON.stringify(data) }),
};
