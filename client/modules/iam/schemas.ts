/**
 * IAM Module - Zod Schemas
 * Used by React Hook Form for validation and as TypeScript type sources
 */

import { z } from 'zod';

export const LoginSchema = z.object({
  username: z
    .string()
    .min(1, 'Username is required')
    .max(50, 'Username must be less than 50 characters'),
  password: z
    .string()
    .min(1, 'Password is required'),
});

export type LoginFormValues = z.infer<typeof LoginSchema>;

export const OrganizationSchema = z.object({
  name: z
    .string()
    .min(1, 'Organization name is required')
    .max(255, 'Name must be less than 255 characters'),
});

export type OrganizationFormValues = z.infer<typeof OrganizationSchema>;

export const CampusSchema = z.object({
  organization_id: z.string().uuid('Invalid organization'),
  name: z.string().min(1, 'Campus name is required').max(255),
  code: z.string().min(1, 'Campus code is required').max(50),
  address: z.string().max(500).optional(),
  postal_code: z.string().max(20).optional(),
  phone: z.string().max(50).optional(),
  email: z.string().email('Invalid email').optional().or(z.literal('')),
  description: z.string().max(1000).optional(),
  is_active: z.boolean().default(true),
});

export type CampusFormValues = z.infer<typeof CampusSchema>;

export const BranchSchema = z.object({
  campus_id: z.string().uuid().optional().nullable(),
  name: z.string().min(1, 'Branch name is required').max(255),
  code: z.string().max(50).optional().nullable(),
  location: z.string().min(1, 'Location is required').max(500),
  address: z.string().max(500).optional(),
  postal_code: z.string().max(20).optional(),
  phone: z.string().max(50).optional(),
  email: z.string().email('Invalid email').optional().or(z.literal('')),
  description: z.string().max(1000).optional(),
  is_active: z.boolean().default(true),
});

export type BranchFormValues = z.infer<typeof BranchSchema>;

export const UserSchema = z.object({
  username: z.string().min(1, 'Username is required').max(50),
  password: z.string().min(8, 'Password must be at least 8 characters').optional(),
  full_name: z.string().min(1, 'Full name is required').max(255),
  email: z.string().email('Invalid email').optional().or(z.literal('')),
  phone: z.string().max(50).optional(),
  role: z.enum([
    'owner',
    'manager',
    'finance',
    'registrar',
    'teacher',
    'head_of_department',
    'counselor',
    'donor_manager',
  ]),
  branch_id: z.string().uuid('Branch is required'),
  gender: z.enum(['male', 'female', 'other']).optional().nullable(),
  department: z.string().max(100).optional(),
  employee_id: z.string().max(50).optional(),
  national_id: z.string().max(50).optional(),
  date_of_birth: z.string().optional().nullable(),
  joining_date: z.string().optional().nullable(),
  is_active: z.boolean().default(true),
});

export type UserFormValues = z.infer<typeof UserSchema>;
