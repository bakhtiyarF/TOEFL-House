/**
 * IAM Module - Public Interface
 * Only these exports may be imported by other modules
 */

export { useAuth, useBranches, useOrganizations, useCampuses, useUsers } from './hooks/useAuth';
export { LoginPage } from './components/LoginPage';
export { RouteGuard } from './components/RouteGuard';
export { AppLayout } from './components/AppLayout';
export type { AuthUser, User, Branch, Campus, Organization, ResolvedPermission, ScopeType } from './types';
