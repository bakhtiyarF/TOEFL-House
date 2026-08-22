/**
 * Notification Bell — Platform Services
 * Shows unread notifications in a dropdown (03 §5)
 */

import { useState } from 'react';
import { Button } from './ui/button';
import { Badge } from './ui/badge';
import { Bell, Check, CheckCheck, Info, AlertTriangle, AlertCircle, CheckCircle2 } from 'lucide-react';

interface Notification {
  id: string;
  title: string;
  message: string;
  type: 'info' | 'warning' | 'critical' | 'success';
  read: boolean;
  time: string;
}

const mockNotifications: Notification[] = [
  { id: '1', title: 'New Student Registered', message: 'Sara Mohammadi has been registered', type: 'success', read: false, time: '5 min ago' },
  { id: '2', title: 'Payment Received', message: '15,000 AFN from Ahmad Rahimi', type: 'info', read: false, time: '22 min ago' },
  { id: '3', title: 'Attendance Warning', message: 'General English L3: attendance below 85%', type: 'warning', read: false, time: '1 hour ago' },
  { id: '4', title: 'Expense Approved', message: 'Office supplies — 3,200 AFN approved', type: 'info', read: true, time: '2 hours ago' },
  { id: '5', title: 'Low Stock Alert', message: 'Grammar in Use is out of stock', type: 'critical', read: true, time: '3 hours ago' },
  { id: '6', title: 'Scholarship Awarded', message: 'Need-Based Aid: 5,000 AFN to Zahra Noori', type: 'success', read: true, time: '4 hours ago' },
];

const typeIcons = {
  info: Info,
  warning: AlertTriangle,
  critical: AlertCircle,
  success: CheckCircle2,
};

const typeColors = {
  info: 'text-blue-600',
  warning: 'text-yellow-600',
  critical: 'text-red-600',
  success: 'text-green-600',
};

export function NotificationBell() {
  const [open, setOpen] = useState(false);
  const [notifications, setNotifications] = useState(mockNotifications);

  const unreadCount = notifications.filter((n) => !n.read).length;

  const markAllRead = () => {
    setNotifications(notifications.map((n) => ({ ...n, read: true })));
  };

  const markRead = (id: string) => {
    setNotifications(notifications.map((n) => n.id === id ? { ...n, read: true } : n));
  };

  return (
    <div className="relative">
      <Button
        variant="ghost"
        size="icon"
        onClick={() => setOpen(!open)}
        className="relative"
      >
        <Bell className="h-5 w-5" />
        {unreadCount > 0 && (
          <span className="absolute -top-1 -end-1 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-[10px] font-bold text-destructive-foreground">
            {unreadCount}
          </span>
        )}
      </Button>

      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
          <div className="absolute end-0 top-full mt-2 z-50 w-80 rounded-lg border bg-background shadow-lg">
            <div className="flex items-center justify-between border-b p-3">
              <h3 className="font-semibold text-sm">Notifications</h3>
              <Button variant="ghost" size="sm" className="text-xs h-7" onClick={markAllRead}>
                <CheckCheck className="h-3 w-3 me-1" />
                Mark all read
              </Button>
            </div>
            <div className="max-h-[360px] overflow-y-auto">
              {notifications.length === 0 ? (
                <div className="p-6 text-center text-sm text-muted-foreground">
                  No notifications
                </div>
              ) : (
                notifications.map((notif) => {
                  const Icon = typeIcons[notif.type];
                  return (
                    <div
                      key={notif.id}
                      className={`flex items-start gap-3 p-3 border-b last:border-0 hover:bg-accent/50 cursor-pointer transition-colors ${!notif.read ? 'bg-accent/30' : ''}`}
                      onClick={() => markRead(notif.id)}
                    >
                      <Icon className={`h-4 w-4 mt-0.5 shrink-0 ${typeColors[notif.type]}`} />
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2">
                          <p className="text-sm font-medium truncate">{notif.title}</p>
                          {!notif.read && <div className="h-2 w-2 rounded-full bg-blue-500 shrink-0" />}
                        </div>
                        <p className="text-xs text-muted-foreground truncate">{notif.message}</p>
                        <p className="text-xs text-muted-foreground mt-0.5">{notif.time}</p>
                      </div>
                    </div>
                  );
                })
              )}
            </div>
          </div>
        </>
      )}
    </div>
  );
}
