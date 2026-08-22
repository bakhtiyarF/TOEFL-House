/**
 * App Layout Component
 * Main application shell with sidebar navigation
 * Uses mock auth for preview
 */

import { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { cn } from '@shared/lib/utils';
import { useMockAuth } from '@app/mockAuth';
import { CommandPalette } from '@shared/components/CommandPalette';
import { NotificationBell } from '@shared/components/NotificationBell';
import {
  LayoutDashboard,
  Users,
  GraduationCap,
  DollarSign,
  Package,
  Heart,
  Settings,
  LogOut,
  Menu,
  X,
  UserCircle,
  Building2,
  ClipboardList,
  UserPlus,
  Moon,
  Sun,
  Search,
} from 'lucide-react';
import { useUIStore } from '@shared/store';
import { useLocaleStore } from '@shared/store/locale';

interface NavItem {
  label: string;
  icon: React.ElementType;
  href: string;
  permission?: string;
}

const navigation: NavItem[] = [
  { label: 'Dashboard', icon: LayoutDashboard, href: '/', permission: 'Dashboard.View' },
  { label: 'Students', icon: GraduationCap, href: '/academic/students', permission: 'Student.View' },
  { label: 'Classes', icon: ClipboardList, href: '/academic/classes', permission: 'Class.View' },
  { label: 'Teachers', icon: Users, href: '/people-hr/teachers', permission: 'Teacher.View' },
  { label: 'Visitors & Leads', icon: UserPlus, href: '/crm/visitors', permission: 'Lead.View' },
  { label: 'Finance', icon: DollarSign, href: '/finance', permission: 'Payment.View' },
  { label: 'Inventory', icon: Package, href: '/inventory', permission: 'Book.View' },
  { label: 'Funding & Impact', icon: Heart, href: '/funding', permission: 'Funding.View' },
  { label: 'Settings', icon: Settings, href: '/settings', permission: 'Settings.View' },
];

export function AppLayout({ children }: { children: React.ReactNode }) {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();
  const { user, logout, hasPermission } = useMockAuth();
  const { darkMode, toggleDarkMode } = useUIStore();
  const { locale, toggleLocale, t } = useLocaleStore();

  const handleLogout = () => {
    logout();
    navigate('/login', { replace: true });
  };

  const filteredNavigation = navigation.filter(
    (item) => !item.permission || hasPermission(item.permission)
  );

  return (
    <div className="min-h-screen bg-background">
      {/* Mobile sidebar backdrop */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={cn(
          'fixed inset-y-0 start-0 z-50 w-64 bg-sidebar border-e border-sidebar-border transform transition-transform duration-200 ease-in-out lg:translate-x-0',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        )}
      >
        <div className="flex h-full flex-col">
          {/* Logo */}
          <div className="flex h-16 items-center justify-between px-6 border-b border-sidebar-border">
            <Link to="/" className="flex items-center gap-2">
              <Building2 className="h-6 w-6 text-sidebar-primary" />
              <span className="text-lg font-semibold text-sidebar-foreground">TOEFL House</span>
            </Link>
            <Button
              variant="ghost"
              size="icon"
              className="lg:hidden"
              onClick={() => setSidebarOpen(false)}
            >
              <X className="h-5 w-5" />
            </Button>
          </div>

          {/* Navigation */}
          <nav className="flex-1 overflow-y-auto p-4 space-y-1">
            {filteredNavigation.map((item) => {
              const Icon = item.icon;
              const isActive = location.pathname === item.href ||
                              (item.href !== '/' && location.pathname.startsWith(item.href));
              return (
                <Link
                  key={item.href}
                  to={item.href}
                  onClick={() => setSidebarOpen(false)}
                  className={cn(
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                    isActive
                      ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                      : 'text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                  )}
                >
                  <Icon className="h-5 w-5" />
                  <span>{item.label}</span>
                </Link>
              );
            })}
          </nav>

          {/* User section */}
          <div className="border-t border-sidebar-border p-4">
            <div className="flex items-center gap-3 mb-3">
              <UserCircle className="h-10 w-10 text-muted-foreground" />
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-sidebar-foreground truncate">
                  {user?.full_name}
                </p>
                <p className="text-xs text-muted-foreground truncate capitalize">
                  {user?.role?.replace('_', ' ')}
                </p>
              </div>
            </div>
            <Button
              variant="outline"
              size="sm"
              className="w-full"
              onClick={handleLogout}
            >
              <LogOut className="h-4 w-4 me-2" />
              Sign out
            </Button>
          </div>
        </div>
      </aside>

      {/* Main content */}
      <div className="lg:ps-64">
        {/* Top bar */}
        <header className="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-border bg-background/95 backdrop-blur px-6">
          <Button
            variant="ghost"
            size="icon"
            className="lg:hidden"
            onClick={() => setSidebarOpen(true)}
          >
            <Menu className="h-5 w-5" />
          </Button>

          {/* Command palette trigger */}
          <button
            onClick={() => document.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', metaKey: true }))}
            className="hidden md:flex items-center gap-2 rounded-md border border-input bg-muted/50 px-3 py-1.5 text-sm text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors flex-1 max-w-sm"
          >
            <Search className="h-4 w-4" />
            <span>Search...</span>
            <kbd className="ms-auto pointer-events-none inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium">
              ⌘K
            </kbd>
          </button>

          <div className="flex-1 md:hidden" />
          <NotificationBell />
          <Button variant="ghost" size="icon" onClick={toggleLocale} title={locale === 'en' ? 'Switch to Dari' : 'Switch to English'}>
            <span className="text-xs font-bold">{locale === 'en' ? 'دری' : 'EN'}</span>
          </Button>
          <Button variant="ghost" size="icon" onClick={toggleDarkMode}>
            {darkMode ? <Sun className="h-5 w-5" /> : <Moon className="h-5 w-5" />}
          </Button>
          {user?.branch && (
            <Badge variant="secondary" className="hidden sm:inline-flex">
              {user.branch.name}
            </Badge>
          )}
        </header>

        {/* Command Palette */}
        <CommandPalette />

        {/* Page content */}
        <main id="main-content" className="p-4 md:p-6">
          {children}
        </main>
      </div>
    </div>
  );
}
