/**
 * Application Routes
 * Thin shell: routing + auth gate only (per 01_TARGET_ARCHITECTURE.md §7)
 */

import { Routes, Route, Navigate } from 'react-router-dom';
import { LoginPage, RouteGuard, AppLayout } from '@modules/iam';
import { DashboardPage } from '@reporting/DashboardPage';
import { StudentsPage } from '@modules/academic/components/StudentsPage';
import { ClassesPage } from '@modules/academic/components/ClassesPage';
import { TeachersPage } from '@modules/people-hr/components/TeachersPage';
import { FinancePage } from '@modules/finance-payroll/components/FinancePage';
import { InventoryPage } from '@modules/inventory/components/InventoryPage';
import { FundingPage } from '@modules/funding-impact/components/FundingPage';
import { SettingsPage } from '@modules/platform-services/components/SettingsPage';
import { VisitorsPage } from '@modules/crm-enrollment/components/VisitorsPage';

export function AppRoutes() {
  return (
    <Routes>
      {/* Public routes */}
      <Route path="/login" element={<LoginPage />} />

      {/* Protected routes */}
      <Route
        path="/"
        element={
          <RouteGuard>
            <AppLayout>
              <DashboardPage />
            </AppLayout>
          </RouteGuard>
        }
      />

      {/* Academic module */}
      <Route
        path="/academic/students"
        element={
          <RouteGuard requiredPermission="Student.View">
            <AppLayout>
              <StudentsPage />
            </AppLayout>
          </RouteGuard>
        }
      />
      <Route
        path="/academic/classes"
        element={
          <RouteGuard requiredPermission="Class.View">
            <AppLayout>
              <ClassesPage />
            </AppLayout>
          </RouteGuard>
        }
      />

      {/* People & HR module */}
      <Route
        path="/people-hr/teachers"
        element={
          <RouteGuard requiredPermission="Teacher.View">
            <AppLayout>
              <TeachersPage />
            </AppLayout>
          </RouteGuard>
        }
      />

      {/* CRM & Enrollment module */}
      <Route
        path="/crm/visitors"
        element={
          <RouteGuard requiredPermission="Lead.View">
            <AppLayout>
              <VisitorsPage />
            </AppLayout>
          </RouteGuard>
        }
      />

      {/* Finance & Payroll module */}
      <Route
        path="/finance"
        element={
          <RouteGuard requiredPermission="Payment.View">
            <AppLayout>
              <FinancePage />
            </AppLayout>
          </RouteGuard>
        }
      />

      {/* Inventory module */}
      <Route
        path="/inventory"
        element={
          <RouteGuard requiredPermission="Book.View">
            <AppLayout>
              <InventoryPage />
            </AppLayout>
          </RouteGuard>
        }
      />

      {/* Funding & Impact module */}
      <Route
        path="/funding"
        element={
          <RouteGuard requiredPermission="Funding.View">
            <AppLayout>
              <FundingPage />
            </AppLayout>
          </RouteGuard>
        }
      />

      {/* Platform Services / Settings */}
      <Route
        path="/settings"
        element={
          <RouteGuard requiredPermission="Settings.View">
            <AppLayout>
              <SettingsPage />
            </AppLayout>
          </RouteGuard>
        }
      />

      {/* Catch-all redirect */}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
