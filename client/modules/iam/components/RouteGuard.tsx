/**
 * Route Guard Component
 * Checks authentication and permissions before rendering children
 * Uses mock auth for preview when backend isn't available
 */

import { Navigate } from 'react-router-dom';
import { useMockAuth } from '@app/mockAuth';
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
  const { isAuthenticated, isLoading, hasPermission } = useMockAuth();

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

  if (requiredPermission && !hasPermission(requiredPermission)) {
    return (
      <div className="flex h-screen items-center justify-center p-4">
        <div className="text-center space-y-4 max-w-md">
          <h1 className="text-2xl font-bold">Access Denied</h1>
          <p className="text-muted-foreground">
            You don't have permission to view this page. Required: <code className="bg-muted px-2 py-1 rounded text-sm">{requiredPermission}</code>
          </p>
        </div>
      </div>
    );
  }

  if (requiredPermissions && requiredPermissions.length > 0) {
    const hasAccess = requireAll
      ? requiredPermissions.every((p) => hasPermission(p))
      : requiredPermissions.some((p) => hasPermission(p));

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
