/**
 * Student Journey Timeline — Academic Module
 *
 * Append-only event log visualization (02 §9.1, 05 §4.3)
 * Shows the complete lifecycle of a student from registration through graduation
 */

import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Badge } from '@shared/components/ui/badge';
import {
  UserPlus, ClipboardCheck, BookOpen, CreditCard, Receipt, CreditCard as CardIcon,
  CalendarCheck, FileText, Award, GraduationCap, StickyNote, ArrowUpCircle,
  BookMarked, MapPin,
} from 'lucide-react';

interface JourneyEvent {
  id: string;
  event_type: string;
  occurred_at: string;
  payload?: Record<string, any>;
  actor_name?: string;
}

interface StudentJourneyTimelineProps {
  studentName: string;
  events?: JourneyEvent[];
}

const eventConfig: Record<string, { icon: React.ElementType; color: string; isFinancial?: boolean }> = {
  STUDENT_REGISTERED: { icon: UserPlus, color: 'bg-blue-500' },
  PLACEMENT_TEST_RECORDED: { icon: ClipboardCheck, color: 'bg-purple-500' },
  PLACEMENT_PASSED: { icon: ClipboardCheck, color: 'bg-green-500' },
  PLACEMENT_FAILED: { icon: ClipboardCheck, color: 'bg-red-500' },
  ENROLLMENT_CREATED: { icon: BookOpen, color: 'bg-indigo-500' },
  ENROLLMENT_STATUS_CHANGED: { icon: BookOpen, color: 'bg-yellow-500' },
  CLASS_ASSIGNED: { icon: MapPin, color: 'bg-teal-500' },
  INVOICE_ISSUED: { icon: Receipt, color: 'bg-orange-500', isFinancial: true },
  PAYMENT_RECORDED: { icon: CreditCard, color: 'bg-green-500', isFinancial: true },
  ID_CARD_ISSUED: { icon: CardIcon, color: 'bg-cyan-500' },
  BOOK_ISSUED: { icon: BookMarked, color: 'bg-amber-500' },
  ATTENDANCE_RECORDED: { icon: CalendarCheck, color: 'bg-lime-500' },
  EXAM_RESULT_RECORDED: { icon: FileText, color: 'bg-violet-500' },
  PROMOTION_DECIDED: { icon: ArrowUpCircle, color: 'bg-emerald-500' },
  STATUS_CHANGED: { icon: StickyNote, color: 'bg-gray-500' },
  GRADUATED: { icon: GraduationCap, color: 'bg-yellow-500' },
  NOTE_ADDED: { icon: StickyNote, color: 'bg-slate-500' },
};

const formatEventLabel = (eventType: string): string => {
  return eventType
    .split('_')
    .map((word) => word.charAt(0) + word.slice(1).toLowerCase())
    .join(' ');
};

const formatDate = (dateStr: string): string => {
  return new Date(dateStr).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const mockEvents: JourneyEvent[] = [
  { id: '1', event_type: 'STUDENT_REGISTERED', occurred_at: '2026-01-15T09:30:00Z', payload: { full_name: 'Ahmad Rahimi' }, actor_name: 'Reception' },
  { id: '2', event_type: 'PLACEMENT_TEST_RECORDED', occurred_at: '2026-01-16T10:00:00Z', payload: { score: 78, recommended_level: 'L3' }, actor_name: 'Mr. Ahmed' },
  { id: '3', event_type: 'PLACEMENT_PASSED', occurred_at: '2026-01-16T10:05:00Z', payload: { score: 78 }, actor_name: 'System' },
  { id: '4', event_type: 'ENROLLMENT_CREATED', occurred_at: '2026-01-17T08:00:00Z', payload: { program: 'General English', level: 'L3' }, actor_name: 'Reception' },
  { id: '5', event_type: 'CLASS_ASSIGNED', occurred_at: '2026-01-17T08:30:00Z', payload: { class_name: 'General English - Level 3' }, actor_name: 'Reception' },
  { id: '6', event_type: 'INVOICE_ISSUED', occurred_at: '2026-01-17T09:00:00Z', payload: { amount: 5000, currency: 'AFN' }, actor_name: 'Finance' },
  { id: '7', event_type: 'PAYMENT_RECORDED', occurred_at: '2026-01-18T14:00:00Z', payload: { amount: 5000, method: 'cash' }, actor_name: 'Reception' },
  { id: '8', event_type: 'ID_CARD_ISSUED', occurred_at: '2026-01-19T11:00:00Z', payload: { card_no: 'ID-2026-0042' }, actor_name: 'Designer' },
  { id: '9', event_type: 'BOOK_ISSUED', occurred_at: '2026-01-20T09:30:00Z', payload: { book: 'Official TOEFL Guide 5th Ed.' }, actor_name: 'Reception' },
  { id: '10', event_type: 'EXAM_RESULT_RECORDED', occurred_at: '2026-03-15T16:00:00Z', payload: { exam: 'Midterm', score: 82 }, actor_name: 'Mr. Ahmed' },
  { id: '11', event_type: 'PROMOTION_DECIDED', occurred_at: '2026-06-30T12:00:00Z', payload: { from: 'L3', to: 'L4', score: 85, attendance: '92%' }, actor_name: 'System' },
];

export function StudentJourneyTimeline({ studentName, events = mockEvents }: StudentJourneyTimelineProps) {
  const financialEvents = events.filter((e) => eventConfig[e.event_type]?.isFinancial);
  const totalPaid = financialEvents
    .filter((e) => e.event_type === 'PAYMENT_RECORDED')
    .reduce((sum, e) => sum + (e.payload?.amount || 0), 0);

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <div>
            <CardTitle className="flex items-center gap-2">
              <MapPin className="h-5 w-5" />
              Student Journey
            </CardTitle>
            <p className="text-sm text-muted-foreground">{studentName} · {events.length} events</p>
          </div>
          {totalPaid > 0 && (
            <Badge variant="outline" className="text-sm">
              Total Paid: {totalPaid.toLocaleString()} AFN
            </Badge>
          )}
        </div>
      </CardHeader>
      <CardContent>
        <div className="relative">
          {/* Timeline line */}
          <div className="absolute start-5 top-0 bottom-0 w-0.5 bg-border" />

          <div className="space-y-6">
            {events.map((event, index) => {
              const config = eventConfig[event.event_type] || { icon: StickyNote, color: 'bg-gray-400' };
              const Icon = config.icon;

              return (
                <div key={event.id} className="relative flex gap-4 ps-2">
                  {/* Timeline dot */}
                  <div className={`relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${config.color} text-white shadow-sm`}>
                    <Icon className="h-4 w-4" />
                  </div>

                  {/* Event content */}
                  <div className="flex-1 min-w-0 pb-2">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="font-medium text-sm">{formatEventLabel(event.event_type)}</span>
                      {config.isFinancial && (
                        <Badge variant="outline" className="text-xs">Financial</Badge>
                      )}
                    </div>
                    <p className="text-xs text-muted-foreground mt-0.5">
                      {formatDate(event.occurred_at)}
                      {event.actor_name && ` · by ${event.actor_name}`}
                    </p>
                    {event.payload && Object.keys(event.payload).length > 0 && (
                      <div className="mt-1.5 flex flex-wrap gap-1.5">
                        {Object.entries(event.payload).slice(0, 4).map(([key, value]) => (
                          <span key={key} className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-xs">
                            <span className="text-muted-foreground me-1">{key}:</span>
                            <span className="font-medium">{String(value)}</span>
                          </span>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
