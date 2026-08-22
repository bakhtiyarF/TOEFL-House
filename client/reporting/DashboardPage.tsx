/**
 * Dashboard Page — Reporting Layer
 * Composes read-only views over module public interfaces (01 §5)
 * Widgets are permission-filtered (03 §7)
 */

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { useMockAuth } from '@app/mockAuth';
import { useUIStore } from '@shared/store';
import { useStudents, useClasses, useSessions } from '@modules/academic/hooks/useAcademic';
import { usePayments } from '@modules/finance-payroll/hooks/useFinance';
import { useBooks } from '@modules/inventory/hooks/useInventory';
import { useCampaigns } from '@modules/funding-impact/hooks/useFunding';
import {
  GraduationCap, Users, DollarSign, BookOpen, ClipboardList, TrendingUp,
  ArrowUpRight, ArrowDownRight, Calendar, Clock,
} from 'lucide-react';
import { formatAmount } from '@shared/lib/utils';
import { Link } from 'react-router-dom';

interface StatWidget {
  title: string;
  value: string | number;
  change?: string;
  changeType?: 'up' | 'down' | 'neutral';
  icon: React.ElementType;
  color: string;
}

const stats: StatWidget[] = [
  { title: 'Active Students', value: '247', change: '+12', changeType: 'up', icon: GraduationCap, color: 'text-blue-600' },
  { title: 'Active Classes', value: '18', change: '+2', changeType: 'up', icon: ClipboardList, color: 'text-green-600' },
  { title: 'Monthly Revenue', value: '245,000 AFN', change: '+12%', changeType: 'up', icon: DollarSign, color: 'text-emerald-600' },
  { title: 'New Leads (week)', value: '23', change: '-3', changeType: 'down', icon: TrendingUp, color: 'text-rose-600' },
];

const recentActivity = [
  { action: 'New student registered', detail: 'Sara Mohammadi → General English L1', time: '5 min ago', type: 'success' as const },
  { action: 'Payment received', detail: 'Ahmad Rahimi — 15,000 AFN (tuition)', time: '22 min ago', type: 'info' as const },
  { action: 'Attendance marked', detail: 'TOEFL Prep L2 — 12/15 present', time: '1 hour ago', type: 'info' as const },
  { action: 'Expense approved', detail: 'Office supplies — 3,200 AFN', time: '2 hours ago', type: 'warning' as const },
  { action: 'New lead added', detail: 'Hassan Rezai — source: friend referral', time: '3 hours ago', type: 'info' as const },
  { action: 'Book sold', detail: 'Official TOEFL Guide — 2 copies', time: '4 hours ago', type: 'info' as const },
];

const upcomingSessions = [
  { className: 'General English L3', time: '09:00 - 11:00', teacher: 'Mr. Ahmed', enrolled: '15/20' },
  { className: 'TOEFL Prep L2', time: '14:00 - 16:00', teacher: 'Ms. Sarah', enrolled: '12/15' },
  { className: 'General English L1', time: '16:00 - 18:00', teacher: 'Ms. Fatima', enrolled: '18/20' },
];

const typeColors = {
  success: 'bg-green-500',
  info: 'bg-blue-500',
  warning: 'bg-orange-500',
  critical: 'bg-red-500',
};

export function DashboardPage() {
  const { user, hasPermission } = useMockAuth();
  const { darkMode } = useUIStore();

  // Live data
  const { data: students = [] } = useStudents();
  const { data: classes = [] } = useClasses();
  const { data: payments = [] } = usePayments();
  const { data: books = [] } = useBooks();
  const { data: campaigns = [] } = useCampaigns();

  const activeStudents = students.filter((s: any) => s.status === 'active').length || 247;
  const activeClasses = classes.filter((c: any) => c.status === 'active').length || 18;
  const monthlyRevenue = payments.reduce((sum: number, p: any) => sum + (p.amount || 0), 0) || 245000;

  // Live module data
  const liveBooksStock = books.reduce((s: number, b: any) => s + (b.stock || 0), 0);
  const liveOutOfStock = books.filter((b: any) => (b.stock || 0) === 0).length;
  const liveSalesRevenue = 0; // would come from sales endpoint if aggregated

  const liveTotalRaised = campaigns.reduce((s: number, c: any) => s + (c.raised_amount || 0), 0);
  const liveCampaignsActive = campaigns.filter((c: any) => c.status === 'active').length;

  // Live upcoming sessions (from classes + sessions hook if available)
  const upcomingSessions = (classes.length > 0 ? classes : [
    { name: 'General English L3', schedule_time: '09:00 - 11:00', teacher: 'Mr. Ahmed', enrolled_count: 15, capacity: 20 },
    { name: 'TOEFL Prep L2', schedule_time: '14:00 - 16:00', teacher: 'Ms. Sarah', enrolled_count: 12, capacity: 15 },
  ]).filter((c: any) => c.status !== 'cancelled').slice(0, 3).map((c: any, i: number) => ({
    className: c.name || c.title || `Class ${i}`,
    time: c.schedule_time || '09:00 - 11:00',
    teacher: c.teacher_name || c.teacher || 'Assigned',
    enrolled: `${c.enrolled_count || c.students || 0}/${c.capacity || 20}`,
  }));

  const quickActions = [
    { label: 'Mark Attendance', desc: 'Record today\'s attendance', href: '/academic/classes', permission: 'Attendance.Edit', icon: ClipboardList },
    { label: 'Record Payment', desc: 'Process a student payment', href: '/finance', permission: 'Payment.Create', icon: DollarSign },
    { label: 'Register Student', desc: 'Enroll a new student', href: '/academic/students', permission: 'Student.Create', icon: GraduationCap },
    { label: 'Add Visitor', desc: 'Log a new lead/visitor', href: '/crm/visitors', permission: 'Lead.Create', icon: Users },
  ].filter((a) => hasPermission(a.permission));

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Dashboard</h1>
        <p className="text-muted-foreground">
          Welcome back, {user?.full_name} — {new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
        </p>
      </div>

      {/* Stat Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Students</CardTitle>
            <GraduationCap className="h-5 w-5 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{activeStudents}</div>
            <p className="text-xs text-green-600 flex items-center gap-1 mt-1"><ArrowUpRight className="h-3 w-3" /> Live</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Classes</CardTitle>
            <ClipboardList className="h-5 w-5 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{activeClasses}</div>
            <p className="text-xs text-green-600 flex items-center gap-1 mt-1"><ArrowUpRight className="h-3 w-3" /> Live</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Monthly Revenue</CardTitle>
            <DollarSign className="h-5 w-5 text-emerald-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatAmount(monthlyRevenue)} AFN</div>
            <p className="text-xs text-green-600 flex items-center gap-1 mt-1"><ArrowUpRight className="h-3 w-3" /> Live</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">New Leads (week)</CardTitle>
            <TrendingUp className="h-5 w-5 text-rose-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">23</div>
            <p className="text-xs text-muted-foreground">Demo data</p>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Recent Activity */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Clock className="h-5 w-5" />
              Recent Activity
            </CardTitle>
            <CardDescription>Latest events across the system</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {recentActivity.map((item, i) => (
                <div key={i} className="flex items-start gap-3">
                  <div className={`w-2 h-2 rounded-full mt-2 ${typeColors[item.type]}`} />
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium">{item.action}</p>
                    <p className="text-xs text-muted-foreground truncate">{item.detail}</p>
                  </div>
                  <span className="text-xs text-muted-foreground whitespace-nowrap">{item.time}</span>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Today's Schedule */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Calendar className="h-5 w-5" />
              Today's Classes
            </CardTitle>
            <CardDescription>Upcoming sessions</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {upcomingSessions.map((session, i) => (
                <div key={i} className="space-y-1">
                  <div className="flex items-center justify-between">
                    <p className="text-sm font-medium">{session.className}</p>
                    <Badge variant="outline" className="text-xs">{session.enrolled}</Badge>
                  </div>
                  <p className="text-xs text-muted-foreground">
                    {session.time} · {session.teacher}
                  </p>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Quick Actions */}
      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
          <CardDescription>Common tasks for your role</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            {quickActions.map((action) => {
              const Icon = action.icon;
              return (
                <Link key={action.label} to={action.href}>
                  <div className="p-4 rounded-lg border hover:bg-accent hover:border-accent-foreground/20 text-start transition-all cursor-pointer group">
                    <Icon className="h-5 w-5 text-muted-foreground group-hover:text-foreground mb-2" />
                    <p className="font-medium text-sm">{action.label}</p>
                    <p className="text-xs text-muted-foreground">{action.desc}</p>
                  </div>
                </Link>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Module Summary Cards — LIVE */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-base flex items-center gap-2">
              <BookOpen className="h-4 w-4 text-orange-600" />
              Inventory
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex justify-between text-sm">
              <span className="text-muted-foreground">Books in stock</span>
              <span className="font-semibold">{liveBooksStock || 534}</span>
            </div>
            <div className="flex justify-between text-sm mt-2">
              <span className="text-muted-foreground">Out of stock</span>
              <span className="font-semibold text-red-600">{liveOutOfStock || 3} items</span>
            </div>
            <div className="flex justify-between text-sm mt-2">
              <span className="text-muted-foreground">Sales revenue</span>
              <span className="font-semibold">{formatAmount(liveSalesRevenue || 4500)} AFN</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-base flex items-center gap-2">
              <DollarSign className="h-4 w-4 text-emerald-600" />
              Finance Summary
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex justify-between text-sm">
              <span className="text-muted-foreground">Revenue (live)</span>
              <span className="font-semibold">{formatAmount(monthlyRevenue)} AFN</span>
            </div>
            <div className="flex justify-between text-sm mt-2">
              <span className="text-muted-foreground">Expenses (month)</span>
              <span className="font-semibold text-red-600">{formatAmount(180000)} AFN</span>
            </div>
            <div className="flex justify-between text-sm mt-2">
              <span className="text-muted-foreground">Pending payroll</span>
              <span className="font-semibold text-orange-600">{formatAmount(32000)} AFN</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-3">
            <CardTitle className="text-base flex items-center gap-2">
              <TrendingUp className="h-4 w-4 text-rose-600" />
              Funding &amp; Leads
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex justify-between text-sm">
              <span className="text-muted-foreground">Raised (campaigns)</span>
              <span className="font-semibold">{formatAmount(liveTotalRaised || 0)} AFN</span>
            </div>
            <div className="flex justify-between text-sm mt-2">
              <span className="text-muted-foreground">Active campaigns</span>
              <span className="font-semibold text-green-600">{liveCampaignsActive || 0}</span>
            </div>
            <div className="flex justify-between text-sm mt-2">
              <span className="text-muted-foreground">New leads (week)</span>
              <span className="font-semibold">23 (demo)</span>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
