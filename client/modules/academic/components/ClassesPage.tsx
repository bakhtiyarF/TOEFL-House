/**
 * Classes Page — Academic Module
 * Class management with sessions, attendance tracking, and enrollment overview
 */

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Input } from '@shared/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@shared/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@shared/components/ui/dialog';
import { Label } from '@shared/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@shared/components/ui/select';
import { AttendanceMarking } from './AttendanceMarking';
import { useClasses, useCreateClass, useSessions, useCreateSession, useUpdateAttendance } from '../hooks/useAcademic';
import { useTeachers } from '@modules/people-hr/hooks/usePeopleHr';
import {
  ClipboardList, Plus, Search, Users, Calendar, Clock,
  CheckCircle2, XCircle, AlertCircle, BookOpen,
} from 'lucide-react';

const ClassSchema = z.object({
  name: z.string().min(3, 'Name required'),
  teacher_id: z.string().optional(),
  capacity: z.string().min(1),
  min_viable_size: z.string().optional(),
  fee: z.string().optional(),
});

type ClassForm = z.infer<typeof ClassSchema>;

interface ClassData {
  id: string;
  name: string;
  teacher: string;
  level: string;
  students: number;
  capacity: number;
  schedule: string;
  status: 'active' | 'completed' | 'cancelled';
  fee: number;
  gender_policy: string;
  start_date: string;
}

interface SessionData {
  id: string;
  class_id: string;
  date: string;
  time: string;
  topic: string;
  status: 'scheduled' | 'completed' | 'cancelled';
  attendance?: { present: number; absent: number; total: number };
}

// Demo data ONLY used as UI fallback when backend is empty (first boot demo mode)
const mockClasses: ClassData[] = [
  { id: '1', name: 'General English - Level 3', teacher: 'Mr. Ahmed Karimi', level: 'L3', students: 15, capacity: 20, schedule: 'Sun/Tue 09:00-11:00', status: 'active', fee: 5000, gender_policy: 'mixed', start_date: '2026-07-01' },
  { id: '2', name: 'TOEFL Preparation - Level 2', teacher: 'Ms. Sarah Noori', level: 'L2', students: 12, capacity: 15, schedule: 'Mon/Wed 14:00-16:00', status: 'active', fee: 7000, gender_policy: 'female', start_date: '2026-07-15' },
  { id: '3', name: 'IELTS Advanced', teacher: 'Mr. Karim Rahimi', level: 'Advanced', students: 8, capacity: 12, schedule: 'Sat 10:00-13:00', status: 'active', fee: 8000, gender_policy: 'mixed', start_date: '2026-08-01' },
];

const mockSessions: SessionData[] = []; // no longer used for live paths

export function ClassesPage() {
  const { data: classesData = [] } = useClasses();
  const { data: teachers = [] } = useTeachers();
  const createClass = useCreateClass();
  const createSession = useCreateSession();
  const updateAttendance = useUpdateAttendance();

  const [searchQuery, setSearchQuery] = useState('');
  const [selectedClass, setSelectedClass] = useState<any>(null);
  const [isAddDialogOpen, setIsAddDialogOpen] = useState(false);
  const [selectedAttendanceSession, setSelectedAttendanceSession] = useState<any>(null);

  const classForm = useForm<ClassForm>({
    resolver: zodResolver(ClassSchema),
    defaultValues: {
      name: '',
      teacher_id: '',
      capacity: '20',
      min_viable_size: '5',
      fee: '5000',
    },
  });

  // Prefer live data; fall back to demo only when backend returns nothing
  const classes = (classesData.length > 0 ? classesData : []) as any[];
  const displayClasses = Array.isArray(classes) && classes.length > 0 ? classes : [];
  const filteredClasses = displayClasses.filter((c: any) =>
    (c.name || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
    (c.teacher || c.teacher_name || '').toLowerCase().includes(searchQuery.toLowerCase())
  );

  // Live sessions for selected class
  const { data: liveSessions = [] } = useSessions(selectedClass?.id || '');

  const classStats = {
    total: classes.length,
    active: classes.filter((c: any) => c.status === 'active').length,
    totalStudents: classes.reduce((sum: number, c: any) => sum + (c.students || c.enrolled_count || 0), 0),
    avgFill: Math.round(
      classes.filter((c: any) => c.status === 'active').reduce((sum: number, c: any) => sum + ((c.students || c.enrolled_count || 0) / (c.capacity || 20) * 100), 0) /
      Math.max(1, classes.filter((c: any) => c.status === 'active').length)
    ),
  };

  const onCreateClassSubmit = (data: ClassForm) => {
    createClass.mutate({
      name: data.name,
      teacher_id: data.teacher_id || undefined,
      capacity: parseInt(data.capacity),
      min_viable_size: data.min_viable_size ? parseInt(data.min_viable_size) : 5,
      fee: data.fee ? parseFloat(data.fee) : 5000,
      branch_id: 'branch-1',
      status: 'active',
    } as any, {
      onSuccess: () => {
        setIsAddDialogOpen(false);
        classForm.reset();
      },
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <ClipboardList className="h-8 w-8" />
            Classes
          </h1>
          <p className="text-muted-foreground">Manage class schedules, sessions, and attendance</p>
        </div>
        <Dialog open={isAddDialogOpen} onOpenChange={setIsAddDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="h-4 w-4 me-2" />
              Create Class
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-lg">
            <DialogHeader>
              <DialogTitle>Create New Class</DialogTitle>
              <DialogDescription>Set up a new class with schedule and capacity</DialogDescription>
            </DialogHeader>
            <form onSubmit={classForm.handleSubmit(onCreateClassSubmit)} className="space-y-4">
              <div className="space-y-2">
                <Label>Class Name *</Label>
                <Input placeholder="e.g. General English - Level 5" {...classForm.register('name')} />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Teacher</Label>
                  <Select onValueChange={(v) => classForm.setValue('teacher_id' as any, v)}>
                    <SelectTrigger><SelectValue placeholder="Select teacher" /></SelectTrigger>
                    <SelectContent>
                      {teachers.length > 0 ? teachers.map((t: any) => (
                        <SelectItem key={t.id} value={t.id}>{t.full_name}</SelectItem>
                      )) : <SelectItem value="">No teachers loaded</SelectItem>}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Capacity *</Label>
                  <Input type="number" {...classForm.register('capacity')} />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Min. Viable Size</Label>
                  <Input type="number" {...classForm.register('min_viable_size')} />
                </div>
                <div className="space-y-2">
                  <Label>Monthly Fee (AFN)</Label>
                  <Input type="number" {...classForm.register('fee')} />
                </div>
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsAddDialogOpen(false)}>Cancel</Button>
                <Button type="submit" disabled={createClass.isPending}>
                  {createClass.isPending ? 'Creating...' : 'Create Class'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Stats */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold">{classStats.total}</div>
            <p className="text-xs text-muted-foreground">Total Classes</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold text-green-600">{classStats.active}</div>
            <p className="text-xs text-muted-foreground">Active</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold">{classStats.totalStudents}</div>
            <p className="text-xs text-muted-foreground">Total Enrolled</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold">{classStats.avgFill}%</div>
            <p className="text-xs text-muted-foreground">Avg. Fill Rate</p>
          </CardContent>
        </Card>
      </div>

      {/* Search */}
      <div className="relative max-w-md">
        <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search classes or teachers..."
          className="ps-10"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
        />
      </div>

      {/* Classes Grid */}
      {filteredClasses.length === 0 && (
        <div className="col-span-full p-8 text-center border rounded-lg bg-muted/30">
          <p className="text-muted-foreground mb-2">No classes found from backend.</p>
          <Button size="sm" onClick={() => setIsAddDialogOpen(true)}>
            <Plus className="h-4 w-4 me-1" /> Create your first class
          </Button>
        </div>
      )}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {filteredClasses.map((cls) => (
          <Card key={cls.id} className={cls.status === 'cancelled' ? 'opacity-60' : ''}>
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <CardTitle className="text-base">{cls.name}</CardTitle>
                <Badge variant={cls.status === 'active' ? 'default' : cls.status === 'completed' ? 'secondary' : 'destructive'}>
                  {cls.status}
                </Badge>
              </div>
              <p className="text-sm text-muted-foreground">{cls.teacher}</p>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex items-center justify-between text-sm">
                <span className="flex items-center gap-1 text-muted-foreground">
                  <Users className="h-4 w-4" /> Students
                </span>
                <span className="font-medium">
                  {cls.students}/{cls.capacity}
                  <span className="text-muted-foreground ms-1">
                    ({Math.round(cls.students / cls.capacity * 100)}%)
                  </span>
                </span>
              </div>
              {/* Capacity bar */}
              <div className="w-full bg-muted rounded-full h-2">
                <div
                  className={`h-2 rounded-full transition-all ${cls.students / cls.capacity > 0.9 ? 'bg-red-500' : cls.students / cls.capacity > 0.7 ? 'bg-yellow-500' : 'bg-green-500'}`}
                  style={{ width: `${Math.min(100, cls.students / cls.capacity * 100)}%` }}
                />
              </div>
              <div className="flex items-center gap-2 text-sm">
                <Calendar className="h-4 w-4 text-muted-foreground" />
                <span>{cls.schedule}</span>
              </div>
              <div className="flex items-center gap-2 text-sm">
                <BookOpen className="h-4 w-4 text-muted-foreground" />
                <span>Level: {cls.level} · Fee: {cls.fee.toLocaleString()} AFN</span>
              </div>
              <div className="pt-2 flex gap-2">
                <Button variant="outline" size="sm" className="flex-1" onClick={() => setSelectedClass(cls)}>
                  Sessions
                </Button>
                <Button variant="outline" size="sm" className="flex-1" onClick={() => setSelectedClass(cls)}>
                  Roster
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Class Sessions Dialog */}
      <Dialog open={!!selectedClass} onOpenChange={() => setSelectedClass(null)}>
        <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
          {selectedClass && (
            <>
              <DialogHeader>
                <DialogTitle>{selectedClass.name}</DialogTitle>
                <DialogDescription>
                  {selectedClass.teacher} · {selectedClass.schedule}
                </DialogDescription>
              </DialogHeader>

              <Tabs defaultValue="sessions">
                <TabsList>
                  <TabsTrigger value="sessions">Sessions</TabsTrigger>
                  <TabsTrigger value="attendance">Attendance</TabsTrigger>
                  <TabsTrigger value="details">Details</TabsTrigger>
                </TabsList>

                <TabsContent value="sessions" className="space-y-4">
                  <div className="flex items-center justify-between">
                    <h4 className="font-medium">Class Sessions</h4>
                    <Button size="sm" onClick={() => {
                      const today = new Date().toISOString().split('T')[0];
                      createSession.mutate({
                        classId: selectedClass.id,
                        data: {
                          date: today,
                          start_time: '09:00',
                          end_time: '11:00',
                          topic: 'New Session'
                        }
                      });
                    }}>
                      <Plus className="h-3 w-3 me-1" /> Add Session
                    </Button>
                  </div>
                  <div className="space-y-3">
                    {liveSessions
                      .filter((s: any) => (s.class_id || s.classId) === selectedClass.id)
                      .map((session: any) => (
                        <div key={session.id} className="flex items-center justify-between p-3 rounded-lg border">
                          <div className="flex items-center gap-3">
                            {session.status === 'completed' ? (
                              <CheckCircle2 className="h-5 w-5 text-green-600" />
                            ) : session.status === 'cancelled' ? (
                              <XCircle className="h-5 w-5 text-red-600" />
                            ) : (
                              <Clock className="h-5 w-5 text-blue-600" />
                            )}
                            <div>
                              <p className="text-sm font-medium">{session.topic}</p>
                              <p className="text-xs text-muted-foreground">
                                {session.date} · {session.start_time || session.time}
                              </p>
                            </div>
                          </div>
                          <div className="text-end">
                            {session.attendance && (
                              <div className="text-sm">
                                <span className="text-green-600 font-medium">{session.attendance.present}</span>
                                <span className="text-muted-foreground">/{session.attendance.total}</span>
                              </div>
                            )}
                            {!session.attendance && session.status === 'scheduled' && (
                              <Badge variant="outline">Scheduled</Badge>
                            )}
                          </div>
                        </div>
                      ))}
                  </div>
                </TabsContent>

                <TabsContent value="attendance">
                  {selectedClass && (
                    <div className="space-y-4">
                      <div>
                        <Label className="text-sm">Select Session to Mark</Label>
                        <Select
                          value={selectedAttendanceSession?.id || ''}
                          onValueChange={(sid) => {
                            const sessList = (liveSessions.length > 0 ? liveSessions : []) as any[];
                            const sess = sessList.find((s: any) => s.id === sid) || null;
                            setSelectedAttendanceSession(sess);
                          }}
                        >
                          <SelectTrigger>
                            <SelectValue placeholder="Choose a session" />
                          </SelectTrigger>
                          <SelectContent>
                            {((liveSessions.length > 0 ? liveSessions : []) as any[])
                              .filter((s: any) => (s.class_id || s.classId) === selectedClass.id)
                              .map((s: any) => (
                                <SelectItem key={s.id} value={s.id}>
                                  {s.date} · {s.start_time || s.time || ''} — {s.topic || 'Session'}
                                </SelectItem>
                              ))}
                            {((liveSessions.length > 0 ? liveSessions : []) as any[]).filter((s: any) => (s.class_id || s.classId) === selectedClass.id).length === 0 && (
                              <SelectItem value="">No sessions yet — create one in Sessions tab</SelectItem>
                            )}
                          </SelectContent>
                        </Select>
                      </div>

                      {selectedAttendanceSession ? (
                        <AttendanceMarking
                          sessionId={selectedAttendanceSession.id}
                          className={selectedClass.name}
                          sessionDate={selectedAttendanceSession.date || new Date().toISOString().split('T')[0]}
                          initialRoster={[]}
                          onSave={(attendance) => {
                            updateAttendance.mutate({
                              sessionId: selectedAttendanceSession.id,
                              attendance,
                            });
                          }}
                        />
                      ) : (
                        <div className="p-4 text-center text-muted-foreground border rounded">
                          Select a session above to mark attendance. Roster will load from backend.
                        </div>
                      )}
                    </div>
                  )}
                </TabsContent>

                <TabsContent value="details" className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <p className="text-sm text-muted-foreground">Level</p>
                      <p className="font-medium">{selectedClass.level}</p>
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground">Fee</p>
                      <p className="font-medium">{selectedClass.fee.toLocaleString()} AFN</p>
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground">Gender Policy</p>
                      <p className="font-medium capitalize">{selectedClass.gender_policy}</p>
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground">Start Date</p>
                      <p className="font-medium">{selectedClass.start_date}</p>
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground">Min. Viable Size</p>
                      <p className="font-medium">{selectedClass.students < 6 ? (
                        <span className="text-orange-600">⚠ Below minimum (6)</span>
                      ) : '5 students'}</p>
                    </div>
                    <div>
                      <p className="text-sm text-muted-foreground">Enrollment</p>
                      <p className="font-medium">{selectedClass.students}/{selectedClass.capacity}</p>
                    </div>
                  </div>
                </TabsContent>
              </Tabs>

              <DialogFooter>
                <Button variant="outline" onClick={() => setSelectedClass(null)}>Close</Button>
              </DialogFooter>
            </>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
