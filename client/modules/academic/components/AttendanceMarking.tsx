/**
 * Attendance Marking Component — Academic Module
 *
 * Allows teachers to mark attendance for a session.
 * Uses rosters table (05 §5 — authoritative attendance mechanism)
 * Status options: present, absent, sick, leave (per schema)
 */

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import {
  CheckCircle2, XCircle, Thermometer, CalendarOff, Clock, Save, Users,
} from 'lucide-react';

type AttendanceStatus = 'present' | 'absent' | 'sick' | 'leave' | 'not_marked';

interface RosterEntry {
  id: string;
  student_id: string;
  student_name: string;
  student_code: string;
  attendance_status: AttendanceStatus;
}

interface AttendanceMarkingProps {
  sessionId: string;
  className: string;
  sessionDate: string;
  initialRoster?: RosterEntry[];
}

const statusConfig: Record<AttendanceStatus, { icon: React.ElementType; color: string; label: string }> = {
  present: { icon: CheckCircle2, color: 'text-green-600 bg-green-100 dark:bg-green-900', label: 'Present' },
  absent: { icon: XCircle, color: 'text-red-600 bg-red-100 dark:bg-red-900', label: 'Absent' },
  sick: { icon: Thermometer, color: 'text-orange-600 bg-orange-100 dark:bg-orange-900', label: 'Sick' },
  leave: { icon: CalendarOff, color: 'text-blue-600 bg-blue-100 dark:bg-blue-900', label: 'Leave' },
  not_marked: { icon: Clock, color: 'text-gray-500 bg-gray-100 dark:bg-gray-800', label: 'Not marked' },
};

const mockRoster: RosterEntry[] = [
  { id: '1', student_id: 's1', student_name: 'Ahmad Rahimi', student_code: 'STU-2026-0001', attendance_status: 'not_marked' },
  { id: '2', student_id: 's2', student_name: 'Fatima Ahmadi', student_code: 'STU-2026-0002', attendance_status: 'not_marked' },
  { id: '3', student_id: 's3', student_name: 'Zahra Noori', student_code: 'STU-2026-0004', attendance_status: 'not_marked' },
  { id: '4', student_id: 's4', student_name: 'Sara Mohammadi', student_code: 'STU-2026-0006', attendance_status: 'not_marked' },
  { id: '5', student_id: 's5', student_name: 'Hassan Rezai', student_code: 'STU-2026-0007', attendance_status: 'not_marked' },
  { id: '6', student_id: 's6', student_name: 'Maryam Faizi', student_code: 'STU-2026-0008', attendance_status: 'not_marked' },
  { id: '7', student_id: 's7', student_name: 'Reza Ahmadi', student_code: 'STU-2026-0009', attendance_status: 'not_marked' },
  { id: '8', student_id: 's8', student_name: 'Nadia Faizi', student_code: 'STU-2026-0010', attendance_status: 'not_marked' },
];

export function AttendanceMarking({ sessionId, className, sessionDate }: AttendanceMarkingProps) {
  const [roster, setRoster] = useState<RosterEntry[]>(mockRoster);
  const [isSaved, setIsSaved] = useState(false);

  const setStatus = (studentId: string, status: AttendanceStatus) => {
    setRoster(roster.map((r) =>
      r.student_id === studentId ? { ...r, attendance_status: status } : r
    ));
    setIsSaved(false);
  };

  const markAllPresent = () => {
    setRoster(roster.map((r) => ({ ...r, attendance_status: 'present' as AttendanceStatus })));
    setIsSaved(false);
  };

  const handleSave = () => {
    // In production: POST to /api/sessions/:id/roster
    setIsSaved(true);
  };

  const stats = {
    present: roster.filter((r) => r.attendance_status === 'present').length,
    absent: roster.filter((r) => r.attendance_status === 'absent').length,
    sick: roster.filter((r) => r.attendance_status === 'sick').length,
    leave: roster.filter((r) => r.attendance_status === 'leave').length,
    notMarked: roster.filter((r) => r.attendance_status === 'not_marked').length,
    total: roster.length,
  };

  const attendanceRate = stats.total > 0
    ? Math.round(((stats.present + stats.sick + stats.leave) / stats.total) * 100)
    : 0;

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <div>
            <CardTitle className="flex items-center gap-2">
              <Users className="h-5 w-5" />
              Mark Attendance
            </CardTitle>
            <p className="text-sm text-muted-foreground">{className} · {sessionDate}</p>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" onClick={markAllPresent}>
              Mark All Present
            </Button>
            <Button size="sm" onClick={handleSave} disabled={stats.notMarked > 0 || isSaved}>
              <Save className="h-4 w-4 me-1" />
              {isSaved ? 'Saved ✓' : 'Save Attendance'}
            </Button>
          </div>
        </div>

        {/* Stats bar */}
        <div className="flex items-center gap-4 mt-3">
          <div className="flex items-center gap-1 text-sm">
            <CheckCircle2 className="h-4 w-4 text-green-600" />
            <span>{stats.present} Present</span>
          </div>
          <div className="flex items-center gap-1 text-sm">
            <XCircle className="h-4 w-4 text-red-600" />
            <span>{stats.absent} Absent</span>
          </div>
          <div className="flex items-center gap-1 text-sm">
            <Thermometer className="h-4 w-4 text-orange-600" />
            <span>{stats.sick} Sick</span>
          </div>
          <div className="flex items-center gap-1 text-sm">
            <CalendarOff className="h-4 w-4 text-blue-600" />
            <span>{stats.leave} Leave</span>
          </div>
          <div className="ms-auto text-sm font-medium">
            {stats.notMarked > 0 && (
              <span className="text-orange-600">{stats.notMarked} unmarked</span>
            )}
            {stats.notMarked === 0 && (
              <span className={attendanceRate >= 85 ? 'text-green-600' : 'text-red-600'}>
                {attendanceRate}% attendance rate
              </span>
            )}
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="space-y-2">
          {roster.map((entry) => {
            const config = statusConfig[entry.attendance_status];
            const Icon = config.icon;
            return (
              <div key={entry.id} className="flex items-center justify-between p-3 rounded-lg border hover:bg-accent/30 transition-colors">
                <div className="flex items-center gap-3">
                  <span className="font-mono text-xs text-muted-foreground w-28">{entry.student_code}</span>
                  <span className="font-medium">{entry.student_name}</span>
                </div>
                <div className="flex items-center gap-1">
                  {(['present', 'absent', 'sick', 'leave'] as AttendanceStatus[]).map((status) => {
                    const sConfig = statusConfig[status];
                    const SIcon = sConfig.icon;
                    const isActive = entry.attendance_status === status;
                    return (
                      <button
                        key={status}
                        onClick={() => setStatus(entry.student_id, status)}
                        className={`p-1.5 rounded-md transition-colors ${
                          isActive
                            ? sConfig.color + ' ring-2 ring-current'
                            : 'text-muted-foreground hover:bg-muted'
                        }`}
                        title={sConfig.label}
                      >
                        <SIcon className="h-4 w-4" />
                      </button>
                    );
                  })}
                </div>
              </div>
            );
          })}
        </div>
      </CardContent>
    </Card>
  );
}
