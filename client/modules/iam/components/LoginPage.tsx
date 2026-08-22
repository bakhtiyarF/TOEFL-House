/**
 * Login Page Component
 * Uses React Hook Form + Zod for validation
 * Falls back to mock auth when no backend is available
 */

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Input } from '@shared/components/ui/input';
import { Label } from '@shared/components/ui/label';
import { Button } from '@shared/components/ui/button';
import { Building2 } from 'lucide-react';
import { useMockAuth } from '@app/mockAuth';
import { LoginSchema, type LoginFormValues } from '../schemas';

export function LoginPage() {
  const navigate = useNavigate();
  const { login, isLoading } = useMockAuth();

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
  } = useForm<LoginFormValues>({
    resolver: zodResolver(LoginSchema),
    defaultValues: {
      username: 'admin',
      password: 'password',
    },
  });

  const onSubmit = async (data: LoginFormValues) => {
    const success = await login(data.username, data.password);
    if (success) {
      navigate('/', { replace: true });
    } else {
      setError('root', {
        message: 'Invalid username or password',
      });
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-background to-muted p-4">
      <div className="w-full max-w-md space-y-6">
        <div className="text-center">
          <div className="flex justify-center mb-4">
            <div className="bg-primary/10 p-3 rounded-full">
              <Building2 className="h-10 w-10 text-primary" />
            </div>
          </div>
          <h1 className="text-3xl font-bold">TOEFL House</h1>
          <p className="text-muted-foreground mt-1">Management Information System</p>
        </div>

        <Card>
          <CardHeader className="space-y-1">
            <CardTitle className="text-xl">Sign In</CardTitle>
            <CardDescription>
              Enter your credentials to access the system
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="username">Username</Label>
                <Input
                  id="username"
                  placeholder="Enter your username"
                  autoComplete="username"
                  {...register('username')}
                />
                {errors.username && (
                  <p className="text-sm text-destructive">{errors.username.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <Input
                  id="password"
                  type="password"
                  placeholder="Enter your password"
                  autoComplete="current-password"
                  {...register('password')}
                />
                {errors.password && (
                  <p className="text-sm text-destructive">{errors.password.message}</p>
                )}
              </div>

              {errors.root && (
                <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                  {errors.root.message}
                </div>
              )}

              <Button type="submit" className="w-full" disabled={isLoading}>
                {isLoading ? (
                  <span className="flex items-center gap-2">
                    <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                    Signing in...
                  </span>
                ) : (
                  'Sign in'
                )}
              </Button>

              <p className="text-xs text-center text-muted-foreground mt-4">
                Demo: use any username/password to sign in
              </p>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
