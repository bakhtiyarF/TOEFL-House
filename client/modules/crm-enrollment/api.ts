const API_BASE = '/api';
async function request<T>(url: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(`${API_BASE}${url}`, { ...options, headers: { 'Content-Type': 'application/json', ...options.headers }, credentials: 'include' });
  if (!res.ok) throw new Error(await res.text());
  return res.status === 204 ? ({} as T) : res.json();
}

export const crmApi = {
  visitors: {
    list: (params?: any) => request(`/visitors?${new URLSearchParams(params || {}).toString()}`),
    create: (d: any) => request('/visitors', { method: 'POST', body: JSON.stringify(d) }),
    convert: (id: string, data?: any) => request(`/visitors/${id}/convert`, { method: 'POST', body: JSON.stringify(data || {}) }),
  },
  followups: (visitorId: string) => request(`/visitors/${visitorId}/followups`),
};
