/**
 * People HR Module API
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

export const peopleHrApi = {
  teachers: {
    list: (params?: any) => request<any[]>(`/teachers?${new URLSearchParams(params || {}).toString()}`),
    get: (id: string) => request(`/teachers/${id}`),
    create: (data: any) => request('/teachers', { method: 'POST', body: JSON.stringify(data) }),
    update: (id: string, data: any) => request(`/teachers/${id}`, { method: 'PATCH', body: JSON.stringify(data) }),
    delete: (id: string) => request(`/teachers/${id}`, { method: 'DELETE' }),
    transfer: (id: string, data: any) => request(`/teachers/${id}/transfer`, { method: 'POST', body: JSON.stringify(data) }),
  },
};
