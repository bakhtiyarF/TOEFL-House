const API_BASE = '/api';
async function request<T>(url: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(`${API_BASE}${url}`, { ...options, headers: { 'Content-Type': 'application/json', ...options.headers }, credentials: 'include' });
  if (!res.ok) throw new Error(await res.text());
  return res.status === 204 ? ({} as T) : res.json();
}

export const inventoryApi = {
  books: {
    list: (params?: any) => request(`/books?${new URLSearchParams(params || {}).toString()}`),
    create: (d: any) => request('/books', { method: 'POST', body: JSON.stringify(d) }),
  },
  sales: {
    create: (d: any) => request('/book-sales', { method: 'POST', body: JSON.stringify(d) }),
  },
};
