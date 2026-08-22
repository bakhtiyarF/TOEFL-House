/**
 * IAM Module - TanStack Query Hooks
 * Server state management for auth, users, branches, etc.
 */

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { authApi, branchApi, campusApi, organizationApi, userApi } from '../api';
import type { AuthUser, Branch, LoginCredentials } from '../types';

// Query keys
export const iamKeys = {
  all: ['iam'] as const,
  auth: () => [...iamKeys.all, 'auth'] as const,
  me: () => [...iamKeys.auth(), 'me'] as const,
  organizations: () => [...iamKeys.all, 'organizations'] as const,
  campuses: () => [...iamKeys.all, 'campuses'] as const,
  branches: () => [...iamKeys.all, 'branches'] as const,
  users: () => [...iamKeys.all, 'users'] as const,
};

/**
 * Current authenticated user with resolved permissions
 */
export function useAuth() {
  const queryClient = useQueryClient();

  const meQuery = useQuery({
    queryKey: iamKeys.me(),
    queryFn: authApi.me,
    retry: false,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });

  const loginMutation = useMutation({
    mutationFn: (credentials: LoginCredentials) => authApi.login(credentials),
    onSuccess: (data) => {
      queryClient.setQueryData(iamKeys.me(), data.user);
    },
  });

  const logoutMutation = useMutation({
    mutationFn: authApi.logout,
    onSuccess: () => {
      queryClient.clear();
    },
  });

  const user = meQuery.data as AuthUser | undefined;
  const isAuthenticated = !!user && meQuery.isSuccess;

  const hasPermission = (code: string): boolean => {
    if (!user?.permissions) return false;
    return user.permissions.some((p) => p.code === code);
  };

  const hasAnyPermission = (codes: string[]): boolean => {
    return codes.some((code) => hasPermission(code));
  };

  return {
    user,
    isAuthenticated,
    isLoading: meQuery.isLoading,
    error: meQuery.error,
    login: loginMutation.mutateAsync,
    logout: logoutMutation.mutateAsync,
    isLoggingIn: loginMutation.isPending,
    isLoggingOut: logoutMutation.isPending,
    hasPermission,
    hasAnyPermission,
  };
}

/**
 * Branches list
 */
export function useBranches(branchId?: string | null) {
  return useQuery({
    queryKey: [...iamKeys.branches(), branchId],
    queryFn: () => branchApi.list(branchId),
  });
}

/**
 * Organizations list
 */
export function useOrganizations() {
  return useQuery({
    queryKey: iamKeys.organizations(),
    queryFn: organizationApi.list,
  });
}

/**
 * Campuses list
 */
export function useCampuses() {
  return useQuery({
    queryKey: iamKeys.campuses(),
    queryFn: campusApi.list,
  });
}

/**
 * Users list
 */
export function useUsers(branchId?: string | null) {
  return useQuery({
    queryKey: [...iamKeys.users(), branchId],
    queryFn: () => userApi.list(branchId),
  });
}

/**
 * Create user mutation
 */
export function useCreateUser() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: userApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: iamKeys.users() });
    },
  });
}

/**
 * Update user mutation
 */
export function useUpdateUser() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<Branch> }) =>
      userApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: iamKeys.users() });
    },
  });
}
