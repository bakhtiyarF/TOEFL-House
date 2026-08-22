/**
 * Password Change Dialog — IAM Module
 *
 * Shown when user.must_change_password is true (04 §5 users table)
 * Forces password change before granting full access
 */

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@shared/components/ui/dialog';
import { Button } from '@shared/components/ui/button';
import { Input } from '@shared/components/ui/input';
import { Label } from '@shared/components/ui/label';
import { Shield, Eye, EyeOff, CheckCircle2 } from 'lucide-react';

const PasswordChangeSchema = z
  .object({
    currentPassword: z.string().min(1, 'Current password is required'),
    newPassword: z
      .string()
      .min(8, 'Password must be at least 8 characters')
      .regex(/[A-Z]/, 'Must contain an uppercase letter')
      .regex(/[a-z]/, 'Must contain a lowercase letter')
      .regex(/[0-9]/, 'Must contain a number'),
    confirmPassword: z.string().min(1, 'Please confirm your password'),
  })
  .refine((data) => data.newPassword === data.confirmPassword, {
    message: 'Passwords do not match',
    path: ['confirmPassword'],
  });

type PasswordChangeFormValues = z.infer<typeof PasswordChangeSchema>;

interface PasswordChangeDialogProps {
  open: boolean;
  onPasswordChanged: () => void;
}

export function PasswordChangeDialog({ open, onPasswordChanged }: PasswordChangeDialogProps) {
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const {
    register,
    handleSubmit,
    formState: { errors },
    watch,
    setError,
  } = useForm<PasswordChangeFormValues>({
    resolver: zodResolver(PasswordChangeSchema),
  });

  const newPassword = watch('newPassword', '');

  const passwordStrength = (() => {
    let score = 0;
    if (newPassword.length >= 8) score++;
    if (newPassword.length >= 12) score++;
    if (/[A-Z]/.test(newPassword)) score++;
    if (/[a-z]/.test(newPassword)) score++;
    if (/[0-9]/.test(newPassword)) score++;
    if (/[^A-Za-z0-9]/.test(newPassword)) score++;
    return score;
  })();

  const strengthLabel = passwordStrength <= 2 ? 'Weak' : passwordStrength <= 4 ? 'Medium' : 'Strong';
  const strengthColor = passwordStrength <= 2 ? 'bg-red-500' : passwordStrength <= 4 ? 'bg-yellow-500' : 'bg-green-500';

  const onSubmit = async (data: PasswordChangeFormValues) => {
    setIsSubmitting(true);
    try {
      // In production: POST /api/auth/change-password
      await new Promise((r) => setTimeout(r, 1000));
      onPasswordChanged();
    } catch {
      setError('currentPassword', { message: 'Current password is incorrect' });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Dialog open={open}>
      <DialogContent className="max-w-md" onPointerDownOutside={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Shield className="h-5 w-5 text-primary" />
            Change Your Password
          </DialogTitle>
          <DialogDescription>
            For security, you must change your password before continuing.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="currentPassword">Current Password</Label>
            <div className="relative">
              <Input
                id="currentPassword"
                type={showPassword ? 'text' : 'password'}
                autoComplete="current-password"
                {...register('currentPassword')}
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute end-3 top-1/2 -translate-y-1/2 text-muted-foreground"
              >
                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
              </button>
            </div>
            {errors.currentPassword && (
              <p className="text-xs text-destructive">{errors.currentPassword.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="newPassword">New Password</Label>
            <Input
              id="newPassword"
              type={showPassword ? 'text' : 'password'}
              autoComplete="new-password"
              {...register('newPassword')}
            />
            {errors.newPassword && (
              <p className="text-xs text-destructive">{errors.newPassword.message}</p>
            )}
            {/* Password strength indicator */}
            {newPassword.length > 0 && (
              <div className="space-y-1">
                <div className="flex gap-1">
                  {[1, 2, 3, 4, 5, 6].map((i) => (
                    <div
                      key={i}
                      className={`h-1 flex-1 rounded-full ${i <= passwordStrength ? strengthColor : 'bg-muted'}`}
                    />
                  ))}
                </div>
                <p className="text-xs text-muted-foreground">{strengthLabel}</p>
              </div>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="confirmPassword">Confirm New Password</Label>
            <Input
              id="confirmPassword"
              type={showPassword ? 'text' : 'password'}
              autoComplete="new-password"
              {...register('confirmPassword')}
            />
            {errors.confirmPassword && (
              <p className="text-xs text-destructive">{errors.confirmPassword.message}</p>
            )}
            {newPassword && watch('confirmPassword') === newPassword && newPassword.length >= 8 && (
              <p className="text-xs text-green-600 flex items-center gap-1">
                <CheckCircle2 className="h-3 w-3" /> Passwords match
              </p>
            )}
          </div>

          <div className="text-xs text-muted-foreground space-y-1">
            <p>Password requirements:</p>
            <ul className="list-disc list-inside space-y-0.5">
              <li className={newPassword.length >= 8 ? 'text-green-600' : ''}>At least 8 characters</li>
              <li className={/[A-Z]/.test(newPassword) ? 'text-green-600' : ''}>One uppercase letter</li>
              <li className={/[a-z]/.test(newPassword) ? 'text-green-600' : ''}>One lowercase letter</li>
              <li className={/[0-9]/.test(newPassword) ? 'text-green-600' : ''}>One number</li>
            </ul>
          </div>

          <DialogFooter>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? 'Changing...' : 'Change Password'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
