/**
 * Dashboard Page — Reporting Layer
 * Composes read-only views over module public interfaces (01 §5)
 */

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Skeleton } from '@shared/components/ui/skeleton';
import { useAuth } from '@modules/iam';
import { GraduationCap, Users, DollarSign, BookOpen, ClipboardList, TrendingUp } from 'lucide-react';

interface DashboardWidgetProps {
  title: string;
  description: string;
  value: string | number;
  icon: React.ElementType;
  trend?: string;
  color?: string;
}

function DashboardWidget({ title, description, value, icon: Icon, trend, color = 'text-primary' }: DashboardWidgetProps) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium">{title}</CardTitle>
        <Icon className={`h-5 w-5 ${color}`} />
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-bold">{value}</div>
        <p className="text-xs text-muted-foreground">{description}</p>
        {trend && <p className="text-xs text-muted-foreground mt-1">{trend}</p>}
      </CardContent>
    </Card>
  );
}

export function DashboardPage() {
  const { user } = useAuth();

  // Mock data — in production these come from each module's public Service interface
  const widgets = [
    {
      title: 'Active Students',
      description: 'Currently enrolled students',
      value: '247',
      icon: GraduationCap,
      color: 'text-blue-600',
    },
    {
      title: 'Active Classes',
      description: 'Running this semester',
      value: '18',
      icon: ClipboardList,
      color: 'text-green-600',
    },
    {
      title: 'Teachers',
      description: 'Active teaching staff',
      value: '12',
      icon: Users,
      color: 'text-purple-600',
    },
    {
      title: 'Monthly Revenue',
      description: 'This month (AFN)',
      value: '245,000',
      icon: DollarSign,
      color: 'text-emerald-600',
      trend: '+12% from last month',
    },
    {
      title: 'Books in Stock',
      description: 'Available inventory',
      value: '534',
      icon: BookOpen,
      color: 'text-orange-600',
    },
    {
      title: 'New Leads',
      description: 'This week',
      value: '23',
      icon: TrendingUp,
      color: 'text-rose-600',
      trend: '+5 since yesterday',
    },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Dashboard</h1>
        <p className="text-muted-foreground">
          Welcome back, {user?.full_name}
        </p>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {widgets.map((widget) => (
          <DashboardWidget key={widget.title} {...widget} />
        ))}
      </div>

      {/* Quick Actions */}
      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
          <CardDescription>Common tasks for your role</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-2 md:grid-cols-3">
            <button className="p-4 rounded-lg border hover:bg-accent text-start transition-colors">
              <p className="font-medium">Mark Attendance</p>
              <p className="text-sm text-muted-foreground">Record today's class attendance</p>
            </button>
            <button className="p-4 rounded-lg border hover:bg-accent text-start transition-colors">
              <p className="font-medium">Record Payment</p>
              <p className="text-sm text-muted-foreground">Process a student payment</p>
            </button>
            <button className="p-4 rounded-lg border hover:bg-accent text-start transition-colors">
              <p className="font-medium">Register Student</p>
              <p className="text-sm text-muted-foreground">Enroll a new student</p>
            </button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
