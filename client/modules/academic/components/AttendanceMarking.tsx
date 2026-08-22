/**
 * Attendance Marking Component — Academic Module
 * Live version: accepts roster + onSave callback
 */
import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { CheckCircle2, XCircle, Thermometer, CalendarOff, Clock, Save, Users } from 'lucide-react';
import { useRoster } from '../hooks/useAcademic';

type AttendanceStatus = 'present' | 'absent' | 'sick' | 'leave' | 'not_marked';

interface RosterEntry {
  id?: string;
  student_id: string;
  student_name?: string;
  student_code?: string;
  full_name?: string;
  attendance_status: AttendanceStatus;
}

interface AttendanceMarkingProps {
  sessionId: string;
  className: string;
  sessionDate: string;
  initialRoster?: RosterEntry[];
  onSave?: (attendance: Record<string, string>) => void;
}

const statusConfig: Record<AttendanceStatus, { icon: React.ElementType; color: string; label: string }> = {
  present: { icon: CheckCircle2, color: 'text-green-600 bg-green-100 dark:bg-green-900', label: 'Present' },
  absent: { icon: XCircle, color: 'text-red-600 bg-red-100 dark:bg-red-900', label: 'Absent' },
  sick: { icon: Thermometer, color: 'text-orange-600 bg-orange-100 dark:bg-orange-900', label: 'Sick' },
  leave: { icon: CalendarOff, color: 'text-blue-600 bg-blue-100 dark:bg-blue-900', label: 'Leave' },
  not_marked: { icon: Clock, color: 'text-gray-500 bg-gray-100 dark:bg-gray-800', label: 'Not marked' },
};

const defaultRoster: RosterEntry[] = [
  { student_id: 's1', student_name: 'Ahmad Rahimi', student_code: 'STU-2026-0001', attendance_status: 'not_marked' },
  { student_id: 's2', student_name: 'Fatima Ahmadi', student_code: 'STU-2026-0002', attendance_status: 'not_marked' },
  { student_id: 's3', student_name: 'Zahra Noori', student_code: 'STU-2026-0004', attendance_status: 'not_marked' },
];

export function AttendanceMarking({ sessionId, className, sessionDate, initialRoster = [], onSave }: AttendanceMarkingProps) {
  const { data: liveRoster = [] } = useRoster(sessionId);

  const [roster, setRoster] = useState<RosterEntry[]>(initialRoster.length ? initialRoster : []);
  const [isSaved, setIsSaved] = useState(false);

  // Prefer live roster data when available
  useEffect(() => {
    if (liveRoster.length > 0) {
      const normalized = liveRoster.map((r: any) => ({
        student_id: r.student_id || r.id,
        student_name: r.student_name || r.full_name,
        student_code: r.student_code,
        attendance_status: (r.attendance_status || 'not_marked') as AttendanceStatus,
      }));
      setRoster(normalized);
    } else if (initialRoster.length > 0) {
      setRoster(initialRoster);
    }
  }, [liveRoster, initialRoster]);

  const setStatus = (studentId: string, status: AttendanceStatus) => {
    setRoster(roster.map((r) => r.student_id === studentId ? { ...r, attendance_status: status } : r));
    setIsSaved(false);
  };

  const markAllPresent = () => {
    setRoster(roster.map((r) => ({ ...r, attendance_status: 'present' as AttendanceStatus })));
    setIsSaved(false);
  };

  const handleSave = () => {
    const attendance: Record<string, string> = {};
    roster.forEach(r => {
      attendance[r.student_id] = r.attendance_status;
    });

    if (onSave) {
      onSave(attendance);
    } else {
      // fallback mock
      console.log('Attendance saved (mock):', attendance);
    }
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

  const attendanceRate = stats.total > 0 ? Math.round(((stats.present + stats.sick + stats.leave) / stats.total) * 100) : 0;

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
            <Button variant="outline" size="sm" onClick={markAllPresent}>Mark All Present</Button>
            <Button size="sm" onClick={handleSave} disabled={stats.notMarked > 0 && !isSaved}>
              <Save className="h-4 w-4 me-1" />
              {isSaved ? 'Saved ✓' : 'Save Attendance'}
            </Button>
          </div>
        </div>

        <div className="flex items-center gap-4 mt-3 text-sm">
          <div className="flex items-center gap-1"><CheckCircle2 className="h-4 w-4 text-green-600" />{stats.present} Present</div>
          <div className="flex items-center gap-1"><XCircle className="h-4 w-4 text-red-600" />{stats.absent} Absent</div>
          <div className="flex items-center gap-1"><Thermometer className="h-4 w-4 text-orange-600" />{stats.sick} Sick</div>
          <div className="flex items-center gap-1"><CalendarOff className="h-4 w-4 text-blue-600" />{stats.leave} Leave</div>
          <div className="ms-auto font-medium">
            {stats.notMarked > 0 ? <span className="text-orange-600">{stats.notMarked} unmarked</span> : <span className={attendanceRate >= 85 ? 'text-green-600' : 'text-red-600'}>{attendanceRate}% rate</span>}
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div className="space-y-2">
          {roster.map((entry) => {
            const config = statusConfig[entry.attendance_status];
            const Icon = config.icon;
            return (
              <div key={entry.student_id} className="flex items-center justify-between p-3 rounded-lg border hover:bg-accent/30">
                <div className="flex items-center gap-3">
                  <span className="font-mono text-xs text-muted-foreground w-28">{entry.student_code || entry.student_id}</span>
                  <span className="font-medium">{entry.student_name || entry.full_name}</span>
                </div>
                <div className="flex items-center gap-1">
                  {(['present', 'absent', 'sick', 'leave'] as AttendanceStatus[]).map((status) => {
                    const sConfig = statusConfig[status];
                    const SIcon = sConfig.icon;
                    const isActive = entry.attendance_status === status;
                    return (
                      <button key={status} onClick={() => setStatus(entry.student_id, status)} className={`p-1.5 rounded-md transition-colors ${isActive ? sConfig.color + ' ring-2 ring-current' : 'text-muted-foreground hover:bg-muted'}`} title={sConfig.label}>
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
