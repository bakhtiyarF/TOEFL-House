/**
 * IAM Module - Type Definitions
 * Based on Document 04 (Repo Bootstrap & IAM Module)
 */

export interface Organization {
  id: string;
  name: string;
  created_at: string;
  updated_at: string;
}

export interface Campus {
  id: string;
  organization_id: string;
  name: string;
  code: string;
  address?: string;
  postal_code?: string;
  phone?: string;
  email?: string;
  description?: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Branch {
  id: string;
  campus_id?: string;
  name: string;
  code?: string;
  location: string;
  address?: string;
  postal_code?: string;
  phone?: string;
  email?: string;
  description?: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export type LegacyRole =
  | 'owner'
  | 'manager'
  | 'finance'
  | 'registrar'
  | 'teacher'
  | 'head_of_department'
  | 'counselor'
  | 'donor_manager';

export type Gender = 'male' | 'female' | 'other';

export interface User {
  id: string;
  username: string;
  full_name: string;
  employee_id?: string;
  email?: string;
  phone?: string;
  address?: string;
  national_id?: string;
  emergency_contact?: string;
  department?: string;
  employment_type?: string;
  employee_status?: string;
  profile_photo_path?: string;
  account_status?: string;
  date_of_birth?: string;
  joining_date?: string;
  gender?: Gender;
  manager_user_id?: string;
  role: LegacyRole;
  branch_id: string;
  linked_teacher_id?: string;
  linked_employee_id?: string;
  linked_partner_id?: string;
  two_factor_enabled: boolean;
  must_change_password: boolean;
  is_active: boolean;
  created_at: string;
  last_login_at?: string;
  last_activity_at?: string;
}

export interface Role {
  id: string;
  code: string;
  name: string;
  description?: string;
  is_system: boolean;
  is_active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string;
}

export interface Permission {
  id: string;
  code: string; // Resource.Action format
  resource: string;
  action: string;
  description?: string;
  category: string;
  is_system: boolean;
  created_at: string;
}

export type ScopeType =
  | 'organization'
  | 'campus'
  | 'branch'
  | 'department'
  | 'program'
  | 'class'
  | 'own';

export interface UserRole {
  id: string;
  user_id: string;
  role_id: string;
  scope_type: ScopeType;
  scope_id?: string;
  is_primary: boolean;
  assigned_by?: string;
  assigned_at: string;
  expires_at?: string;
}

export interface RolePermission {
  id: string;
  role_id: string;
  permission_id: string;
  default_scope: ScopeType;
}

export interface PermissionOverride {
  id: string;
  user_id: string;
  permission_id: string;
  effect: 'grant' | 'deny';
  scope_type: ScopeType;
  scope_id?: string;
  reason?: string;
  granted_by?: string;
  created_at: string;
  expires_at?: string;
}

export interface RoleDelegation {
  id: string;
  from_user_id: string;
  to_user_id: string;
  role_id: string;
  scope_type: ScopeType;
  scope_id?: string;
  reason?: string;
  starts_at: string;
  ends_at: string;
  created_by?: string;
  is_active: boolean;
}

export interface ResolvedPermission {
  code: string;
  scope: ScopeType;
  source: 'role' | 'delegation' | 'override';
}

export interface AuthUser extends User {
  permissions: ResolvedPermission[];
  branch?: Branch;
}

export interface LoginCredentials {
  username: string;
  password: string;
}

export interface BranchScopeResult {
  branchId: string | null;
  isAll: boolean;
}
