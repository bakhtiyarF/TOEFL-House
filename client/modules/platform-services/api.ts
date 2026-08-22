const API_BASE = '/api';
async function request<T>(url: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(`${API_BASE}${url}`, { ...options, headers: { 'Content-Type': 'application/json', ...options.headers }, credentials: 'include' });
  if (!res.ok) throw new Error(await res.text());
  return res.status === 204 ? ({} as T) : res.json();
}

export const platformApi = {
  search: (q: string, type = 'all') => request(`/search?q=${encodeURIComponent(q)}&type=${type}`),
  rules: { list: () => request('/rules') },
  notifications: { list: () => request('/notifications'), markRead: (id: string) => request(`/notifications/${id}/read`, { method: 'PATCH' }) },
  settings: { get: (k: string) => request(`/settings/${k}`) },
};
