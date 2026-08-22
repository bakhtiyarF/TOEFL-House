/**
 * Funding & Impact Module - API Client
 * Donors, campaigns, donations, scholarships (live backend)
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

export interface Donor {
  id: string;
  full_name: string;
  type: 'individual' | 'organization' | 'ngo' | 'government';
  phone?: string;
  email?: string;
  country?: string;
  total_donated?: number;
  donations_count?: number;
}

export interface Campaign {
  id: string;
  name: string;
  description?: string;
  target_amount: number;
  raised_amount: number;
  status: string;
  progress_percent?: number;
  donors?: number;
  branch_id?: string;
}

export interface Donation {
  id: string;
  donor_id: string;
  campaign_id?: string;
  amount: number;
  date: string;
  restricted?: boolean;
  restriction_note?: string;
  receipt_no?: string;
}

export interface Scholarship {
  id: string;
  name: string;
  total_budget: number;
  allocated_amount: number;
  remaining_budget?: number;
  utilization_percent?: number;
  status: string;
}

export interface ImpactMetric {
  id: string;
  name: string;
  current_value: number;
  target_value: number;
  category: string;
  progress_percent?: number;
}

export const fundingApi = {
  donors: {
    list: () => request<Donor[]>('/donors'),
    create: (data: Partial<Donor>) => request<Donor>('/donors', { method: 'POST', body: JSON.stringify(data) }),
  },
  campaigns: {
    list: (params?: any) => {
      const q = new URLSearchParams(params || {}).toString();
      return request<Campaign[]>(`/funding-campaigns?${q}`);
    },
    create: (data: Partial<Campaign>) => request<Campaign>('/funding-campaigns', { method: 'POST', body: JSON.stringify(data) }),
  },
  donations: {
    list: () => request<Donation[]>('/donations'),
    create: (data: Partial<Donation & { branch_id?: string }>) =>
      request<Donation>('/donations', { method: 'POST', body: JSON.stringify(data) }),
  },
  scholarships: {
    list: (params?: any) => {
      const q = new URLSearchParams(params || {}).toString();
      return request<Scholarship[]>(`/scholarships?${q}`);
    },
    award: (scholarshipId: string, data: { student_id: string; amount: number; semester?: string; notes?: string }) =>
      request<any>(`/scholarships/${scholarshipId}/awards`, { method: 'POST', body: JSON.stringify(data) }),
  },
  impact: {
    list: (params?: any) => {
      const q = new URLSearchParams(params || {}).toString();
      return request<ImpactMetric[]>(`/impact-metrics?${q}`);
    },
  },
};
