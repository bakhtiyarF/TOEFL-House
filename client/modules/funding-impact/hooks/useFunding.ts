/**
 * Funding & Impact Module - TanStack Query Hooks
 * Live data for donors, campaigns, donations, scholarships, impact
 */

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { fundingApi, type Donor, type Campaign, type Donation, type Scholarship, type ImpactMetric } from '../api';

export const fundingKeys = {
  all: ['funding'] as const,
  donors: () => [...fundingKeys.all, 'donors'] as const,
  campaigns: (p?: any) => [...fundingKeys.all, 'campaigns', p] as const,
  donations: () => [...fundingKeys.all, 'donations'] as const,
  scholarships: (p?: any) => [...fundingKeys.all, 'scholarships', p] as const,
  impact: (p?: any) => [...fundingKeys.all, 'impact', p] as const,
};

export function useDonors() {
  return useQuery({
    queryKey: fundingKeys.donors(),
    queryFn: () => fundingApi.donors.list(),
    staleTime: 60_000,
  });
}

export function useCampaigns(params?: any) {
  return useQuery({
    queryKey: fundingKeys.campaigns(params),
    queryFn: () => fundingApi.campaigns.list(params),
    staleTime: 60_000,
  });
}

export function useDonations() {
  return useQuery({
    queryKey: fundingKeys.donations(),
    queryFn: () => fundingApi.donations.list(),
    staleTime: 30_000,
  });
}

export function useScholarships(params?: any) {
  return useQuery({
    queryKey: fundingKeys.scholarships(params),
    queryFn: () => fundingApi.scholarships.list(params),
    staleTime: 60_000,
  });
}

export function useImpactMetrics(params?: any) {
  return useQuery({
    queryKey: fundingKeys.impact(params),
    queryFn: () => fundingApi.impact.list(params),
    staleTime: 120_000,
  });
}

export function useCreateDonor() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: fundingApi.donors.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: fundingKeys.donors() }),
  });
}

export function useCreateCampaign() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: fundingApi.campaigns.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: fundingKeys.campaigns() }),
  });
}

export function useCreateDonation() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: fundingApi.donations.create,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: fundingKeys.donations() });
      qc.invalidateQueries({ queryKey: fundingKeys.campaigns() });
      qc.invalidateQueries({ queryKey: ['finance'] }); // income sync
    },
  });
}

export function useAwardScholarship() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ scholarshipId, data }: { scholarshipId: string; data: { student_id: string; amount: number; semester?: string; notes?: string } }) =>
      fundingApi.scholarships.award(scholarshipId, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: fundingKeys.scholarships() });
      qc.invalidateQueries({ queryKey: ['academic', 'students'] });
    },
  });
}
