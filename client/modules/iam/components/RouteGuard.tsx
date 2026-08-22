/**
 * Route Guard Component
 * Checks authentication and permissions before rendering children
 */

import { Navigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { Skeleton } from '@shared/components/ui/skeleton';

interface RouteGuardProps {
  children: React.ReactNode;
  requiredPermission?: string;
  requiredPermissions?: string[];
  requireAll?: boolean;
}

export function RouteGuard({
  children,
  requiredPermission,
  requiredPermissions,
  requireAll = false,
}: RouteGuardProps) {
  const { isAuthenticated, isLoading, hasPermission, hasAnyPermission } = useAuth();

  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div className="space-y-4 w-full max-w-md">
          <Skeleton className="h-8 w-3/4" />
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-2/3" />
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  // Check single permission
  if (requiredPermission && !hasPermission(requiredPermission)) {
    return (
      <div className="flex h-screen items-center justify-center p-4">
        <div className="text-center space-y-4 max-w-md">
          <h1 className="text-2xl font-bold">Access Denied</h1>
          <p className="text-muted-foreground">
            You don't have permission to view this page. Please contact your administrator if you believe this is an error.
          </p>
        </div>
      </div>
    );
  }

  // Check multiple permissions
  if (requiredPermissions && requiredPermissions.length > 0) {
    const hasAccess = requireAll
      ? requiredPermissions.every((p) => hasPermission(p))
      : hasAnyPermission(requiredPermissions);

    if (!hasAccess) {
      return (
        <div className="flex h-screen items-center justify-center p-4">
          <div className="text-center space-y-4 max-w-md">
            <h1 className="text-2xl font-bold">Access Denied</h1>
            <p className="text-muted-foreground">
              You don't have the required permissions to view this page.
            </p>
          </div>
        </div>
      );
    }
  }

  return <>{children}</>;
}
