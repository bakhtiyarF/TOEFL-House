/**
 * Inventory Module - API Client
 * Books, sales, restock, refunds (live backend)
 */

const API_BASE = '/api';

async function request<T>(url: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(`${API_BASE}${url}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...options.headers,
    },
    credentials: 'include',
  });
  if (!res.ok) {
    const error = await res.json().catch(() => ({}));
    throw new Error(error.message || 'Request failed');
  }
  if (res.status === 204) return {} as T;
  return res.json();
}

export interface Book {
  id: string;
  title: string;
  price: number;
  purchase_price?: number;
  stock: number;
  is_chapter: boolean;
  total_sold?: number;
  branch_id?: string;
}

export interface BookSale {
  id: string;
  book_id: string;
  book_title?: string;
  quantity: number;
  total_amount: number;
  net_amount: number;
  payment_method: string;
  status: string;
  date: string;
  customer_name?: string;
  student_id?: string;
}

export const inventoryApi = {
  books: {
    list: (params?: { search?: string; branch_id?: string }) => {
      const q = new URLSearchParams(params as any).toString();
      return request<Book[]>(`/books?${q}`);
    },
    create: (data: Partial<Book>) => request<Book>('/books', { method: 'POST', body: JSON.stringify(data) }),
    restock: (id: string, data: { quantity: number; price?: number; purchase_price?: number }) =>
      request<Book>(`/books/${id}/restock`, { method: 'POST', body: JSON.stringify(data) }),
    sell: (id: string, data: { quantity: number; discount_amount?: number; payment_method: string; customer_name?: string; student_id?: string }) =>
      request<any>(`/books/${id}/sell`, { method: 'POST', body: JSON.stringify(data) }),
  },
  sales: {
    list: (params?: any) => {
      const q = new URLSearchParams(params || {}).toString();
      return request<BookSale[]>(`/book-sales?${q}`);
    },
    refund: (saleId: string) => request<any>(`/book-sales/${saleId}/refund`, { method: 'POST' }),
  },
};
