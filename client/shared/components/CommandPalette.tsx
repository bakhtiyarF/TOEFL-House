/**
 * Command Palette — Global Search (cmdk)
 * Permission-filtered: only shows what the user can actually open (03 §5)
 */

import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Command } from 'cmdk';
import { useMockAuth } from '@app/mockAuth';
import {
  GraduationCap, ClipboardList, Users, DollarSign, Package,
  Heart, Settings, UserPlus, Search, LayoutDashboard,
} from 'lucide-react';

interface CommandItem {
  label: string;
  description: string;
  icon: React.ElementType;
  href: string;
  permission: string;
  category: string;
}

const allCommands: CommandItem[] = [
  { label: 'Dashboard', description: 'View system overview', icon: LayoutDashboard, href: '/', permission: 'Dashboard.View', category: 'Navigation' },
  { label: 'Students', description: 'Manage student records', icon: GraduationCap, href: '/academic/students', permission: 'Student.View', category: 'Navigation' },
  { label: 'Add Student', description: 'Register a new student', icon: GraduationCap, href: '/academic/students', permission: 'Student.Create', category: 'Actions' },
  { label: 'Classes', description: 'Manage class schedules', icon: ClipboardList, href: '/academic/classes', permission: 'Class.View', category: 'Navigation' },
  { label: 'Teachers', description: 'Manage teaching staff', icon: Users, href: '/people-hr/teachers', permission: 'Teacher.View', category: 'Navigation' },
  { label: 'Visitors & Leads', description: 'CRM pipeline management', icon: UserPlus, href: '/crm/visitors', permission: 'Lead.View', category: 'Navigation' },
  { label: 'Finance', description: 'Payments and transactions', icon: DollarSign, href: '/finance', permission: 'Payment.View', category: 'Navigation' },
  { label: 'Record Payment', description: 'Process a student payment', icon: DollarSign, href: '/finance', permission: 'Payment.Create', category: 'Actions' },
  { label: 'Inventory', description: 'Books and stock management', icon: Package, href: '/inventory', permission: 'Book.View', category: 'Navigation' },
  { label: 'Funding & Impact', description: 'Donors and scholarships', icon: Heart, href: '/funding', permission: 'Funding.View', category: 'Navigation' },
  { label: 'Settings', description: 'System configuration', icon: Settings, href: '/settings', permission: 'Settings.View', category: 'Navigation' },
  { label: 'Business Rules', description: 'Rule engine admin', icon: Settings, href: '/settings', permission: 'Rule.View', category: 'Admin' },
];

export function CommandPalette() {
  const [open, setOpen] = useState(false);
  const navigate = useNavigate();
  const { hasPermission } = useMockAuth();

  // Keyboard shortcut: Cmd/Ctrl + K
  useEffect(() => {
    const down = (e: KeyboardEvent) => {
      if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
        e.preventDefault();
        setOpen((prev) => !prev);
      }
    };
    document.addEventListener('keydown', down);
    return () => document.removeEventListener('keydown', down);
  }, []);

  const filteredCommands = allCommands.filter((cmd) => hasPermission(cmd.permission));
  const categories = [...new Set(filteredCommands.map((c) => c.category))];

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-[100] bg-black/50" onClick={() => setOpen(false)}>
      <div className="flex items-start justify-center pt-[20vh]">
        <div
          className="w-full max-w-lg bg-background rounded-lg border shadow-2xl overflow-hidden"
          onClick={(e) => e.stopPropagation()}
        >
          <Command className="w-full">
            <div className="flex items-center border-b px-3">
              <Search className="me-2 h-4 w-4 shrink-0 opacity-50" />
              <Command.Input
                placeholder="Search commands..."
                className="flex h-11 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
              />
              <kbd className="pointer-events-none inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground">
                ESC
              </kbd>
            </div>
            <Command.List className="max-h-[300px] overflow-y-auto p-2">
              <Command.Empty className="py-6 text-center text-sm text-muted-foreground">
                No results found.
              </Command.Empty>
              {categories.map((category) => (
                <Command.Group key={category} heading={category} className="py-1">
                  {filteredCommands
                    .filter((c) => c.category === category)
                    .map((cmd) => {
                      const Icon = cmd.icon;
                      return (
                        <Command.Item
                          key={cmd.label + cmd.category}
                          onSelect={() => {
                            navigate(cmd.href);
                            setOpen(false);
                          }}
                          className="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm cursor-pointer aria-selected:bg-accent aria-selected:text-accent-foreground"
                        >
                          <Icon className="h-4 w-4 text-muted-foreground" />
                          <div className="flex-1">
                            <span>{cmd.label}</span>
                            <span className="ms-2 text-xs text-muted-foreground">{cmd.description}</span>
                          </div>
                        </Command.Item>
                      );
                    })}
                </Command.Group>
              ))}
            </Command.List>
          </Command>
        </div>
      </div>
    </div>
  );
}
