const API_BASE = '/api';
async function request<T>(url: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(`${API_BASE}${url}`, { ...options, headers: { 'Content-Type': 'application/json', ...options.headers }, credentials: 'include' });
  if (!res.ok) throw new Error(await res.text());
  return res.status === 204 ? ({} as T) : res.json();
}

export const fundingApi = {
  donors: { list: () => request('/donors'), create: (d: any) => request('/donors', { method: 'POST', body: JSON.stringify(d) }) },
  donations: { list: () => request('/donations'), create: (d: any) => request('/donations', { method: 'POST', body: JSON.stringify(d) }) },
  campaigns: { list: () => request('/funding-campaigns') },
};
