/**
 * IAM Module - API Client
 * Typed fetch calls consumed by TanStack Query hooks
 */

import type {
  AuthUser,
  Branch,
  Campus,
  LoginCredentials,
  Organization,
  Permission,
  Role,
  User,
} from './types';

const API_BASE = '/api';

async function request<T>(
  url: string,
  options: RequestInit = {}
): Promise<T> {
  const response = await fetch(`${API_BASE}${url}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...options.headers,
    },
    credentials: 'include', // Sanctum SPA mode
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    throw new ApiError(response.status, error.message || 'Request failed', error);
  }

  if (response.status === 204) {
    return {} as T;
  }

  return response.json();
}

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public data?: Record<string, unknown>
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

// Auth endpoints
export const authApi = {
  login: (credentials: LoginCredentials) =>
    request<{ user: AuthUser }>('/auth/login', {
      method: 'POST',
      body: JSON.stringify(credentials),
    }),

  logout: () =>
    request<void>('/auth/logout', { method: 'POST' }),

  me: () => request<AuthUser>('/auth/me'),
};

// Organization endpoints
export const organizationApi = {
  list: () => request<Organization[]>('/organizations'),
  get: (id: string) => request<Organization>(`/organizations/${id}`),
  create: (data: Partial<Organization>) =>
    request<Organization>('/organizations', {
      method: 'POST',
      body: JSON.stringify(data),
    }),
  update: (id: string, data: Partial<Organization>) =>
    request<Organization>(`/organizations/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(data),
    }),
  delete: (id: string) =>
    request<void>(`/organizations/${id}`, { method: 'DELETE' }),
};

// Campus endpoints
export const campusApi = {
  list: () => request<Campus[]>('/campuses'),
  get: (id: string) => request<Campus>(`/campuses/${id}`),
  create: (data: Partial<Campus>) =>
    request<Campus>('/campuses', {
      method: 'POST',
      body: JSON.stringify(data),
    }),
  update: (id: string, data: Partial<Campus>) =>
    request<Campus>(`/campuses/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(data),
    }),
  delete: (id: string) =>
    request<void>(`/campuses/${id}`, { method: 'DELETE' }),
};

// Branch endpoints
export const branchApi = {
  list: (branchId?: string | null) => {
    const params = branchId ? `?branch_id=${branchId}` : '';
    return request<Branch[]>(`/branches${params}`);
  },
  get: (id: string) => request<Branch>(`/branches/${id}`),
  create: (data: Partial<Branch>) =>
    request<Branch>('/branches', {
      method: 'POST',
      body: JSON.stringify(data),
    }),
  update: (id: string, data: Partial<Branch>) =>
    request<Branch>(`/branches/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(data),
    }),
  delete: (id: string) =>
    request<void>(`/branches/${id}`, { method: 'DELETE' }),
};

// User endpoints
export const userApi = {
  list: (branchId?: string | null) => {
    const params = branchId ? `?branch_id=${branchId}` : '';
    return request<User[]>(`/users${params}`);
  },
  get: (id: string) => request<User>(`/users/${id}`),
  create: (data: Partial<User> & { password: string }) =>
    request<User>('/users', {
      method: 'POST',
      body: JSON.stringify(data),
    }),
  update: (id: string, data: Partial<User>) =>
    request<User>(`/users/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(data),
    }),
  delete: (id: string) =>
    request<void>(`/users/${id}`, { method: 'DELETE' }),
};

// Role & Permission endpoints (read-only catalogs)
export const roleApi = {
  list: () => request<Role[]>('/roles'),
};

export const permissionApi = {
  list: () => request<Permission[]>('/permissions'),
};
