/**
 * Classes Page — Academic Module
 * Class management with sessions, attendance tracking, and enrollment overview
 */

import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Input } from '@shared/components/ui/input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@shared/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@shared/components/ui/dialog';
import { Label } from '@shared/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@shared/components/ui/select';
import {
  ClipboardList, Plus, Search, Users, Calendar, Clock,
  CheckCircle2, XCircle, AlertCircle, BookOpen,
} from 'lucide-react';

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

const mockClasses: ClassData[] = [
  { id: '1', name: 'General English - Level 3', teacher: 'Mr. Ahmed Karimi', level: 'L3', students: 15, capacity: 20, schedule: 'Sun/Tue 09:00-11:00', status: 'active', fee: 5000, gender_policy: 'mixed', start_date: '2026-07-01' },
  { id: '2', name: 'TOEFL Preparation - Level 2', teacher: 'Ms. Sarah Noori', level: 'L2', students: 12, capacity: 15, schedule: 'Mon/Wed 14:00-16:00', status: 'active', fee: 7000, gender_policy: 'female', start_date: '2026-07-15' },
  { id: '3', name: 'IELTS Advanced', teacher: 'Mr. Karim Rahimi', level: 'Advanced', students: 8, capacity: 12, schedule: 'Sat 10:00-13:00', status: 'active', fee: 8000, gender_policy: 'mixed', start_date: '2026-08-01' },
  { id: '4', name: 'General English - Level 1', teacher: 'Ms. Fatima Ahmadi', level: 'L1', students: 18, capacity: 20, schedule: 'Sun/Tue 16:00-18:00', status: 'active', fee: 5000, gender_policy: 'female', start_date: '2026-07-01' },
  { id: '5', name: 'Business English', teacher: 'Mr. Ali Hussaini', level: 'B1', students: 0, capacity: 15, schedule: 'Mon/Wed 09:00-11:00', status: 'cancelled', fee: 6000, gender_policy: 'mixed', start_date: '2026-08-15' },
  { id: '6', name: 'General English - Level 4', teacher: 'Mr. Ahmed Karimi', level: 'L4', students: 10, capacity: 20, schedule: 'Tue/Thu 11:00-13:00', status: 'active', fee: 5000, gender_policy: 'mixed', start_date: '2026-07-01' },
];

const mockSessions: SessionData[] = [
  { id: 's1', class_id: '1', date: '2026-08-22', time: '09:00-11:00', topic: 'Reading Comprehension - Unit 5', status: 'scheduled' },
  { id: 's2', class_id: '1', date: '2026-08-20', time: '09:00-11:00', topic: 'Grammar - Conditionals', status: 'completed', attendance: { present: 13, absent: 2, total: 15 } },
  { id: 's3', class_id: '1', date: '2026-08-18', time: '09:00-11:00', topic: 'Writing - Essay Structure', status: 'completed', attendance: { present: 14, absent: 1, total: 15 } },
  { id: 's4', class_id: '2', date: '2026-08-22', time: '14:00-16:00', topic: 'TOEFL Speaking Practice', status: 'scheduled' },
  { id: 's5', class_id: '2', date: '2026-08-20', time: '14:00-16:00', topic: 'TOEFL Reading Strategies', status: 'completed', attendance: { present: 11, absent: 1, total: 12 } },
];

export function ClassesPage() {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedClass, setSelectedClass] = useState<ClassData | null>(null);
  const [isAddDialogOpen, setIsAddDialogOpen] = useState(false);

  const filteredClasses = mockClasses.filter((c) =>
    c.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    c.teacher.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const classStats = {
    total: mockClasses.length,
    active: mockClasses.filter((c) => c.status === 'active').length,
    totalStudents: mockClasses.reduce((sum, c) => sum + c.students, 0),
    avgFill: Math.round(mockClasses.filter((c) => c.status === 'active').reduce((sum, c) => sum + (c.students / c.capacity * 100), 0) / mockClasses.filter((c) => c.status === 'active').length),
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
            <div className="space-y-4">
              <div className="space-y-2">
                <Label>Class Name</Label>
                <Input placeholder="e.g. General English - Level 5" />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Teacher</Label>
                  <Select>
                    <SelectTrigger><SelectValue placeholder="Select teacher" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="1">Mr. Ahmed Karimi</SelectItem>
                      <SelectItem value="2">Ms. Sarah Noori</SelectItem>
                      <SelectItem value="3">Mr. Karim Rahimi</SelectItem>
                      <SelectItem value="4">Ms. Fatima Ahmadi</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Program Level</Label>
                  <Select>
                    <SelectTrigger><SelectValue placeholder="Select level" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="L1">Level 1</SelectItem>
                      <SelectItem value="L2">Level 2</SelectItem>
                      <SelectItem value="L3">Level 3</SelectItem>
                      <SelectItem value="L4">Level 4</SelectItem>
                      <SelectItem value="L5">Level 5</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Capacity</Label>
                  <Input type="number" placeholder="20" />
                </div>
                <div className="space-y-2">
                  <Label>Min. Viable Size</Label>
                  <Input type="number" placeholder="5" />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Schedule</Label>
                  <Input placeholder="e.g. Sun/Tue 09:00-11:00" />
                </div>
                <div className="space-y-2">
                  <Label>Monthly Fee (AFN)</Label>
                  <Input type="number" placeholder="5000" />
                </div>
              </div>
              <div className="space-y-2">
                <Label>Gender Policy</Label>
                <Select>
                  <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="mixed">Mixed</SelectItem>
                    <SelectItem value="female">Female only</SelectItem>
                    <SelectItem value="male">Male only</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Start Date</Label>
                  <Input type="date" />
                </div>
                <div className="space-y-2">
                  <Label>End Date</Label>
                  <Input type="date" />
                </div>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setIsAddDialogOpen(false)}>Cancel</Button>
              <Button onClick={() => setIsAddDialogOpen(false)}>Create Class</Button>
            </DialogFooter>
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
                <Button variant="outline" size="sm" className="flex-1">
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
                    <Button size="sm">
                      <Plus className="h-3 w-3 me-1" /> Add Session
                    </Button>
                  </div>
                  <div className="space-y-3">
                    {mockSessions
                      .filter((s) => s.class_id === selectedClass.id)
                      .map((session) => (
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
                                {session.date} · {session.time}
                              </p>
                            </div>
                          </div>
                          <div className="text-end">
                            {session.attendance && (
                              <div className="text-sm">
                                <span className="text-green-600 font-medium">{session.attendance.present}</span>
                                <span className="text-muted-foreground">/{session.attendance.total}</span>
                                <p className="text-xs text-muted-foreground">
                                  {Math.round(session.attendance.present / session.attendance.total * 100)}% attendance
                                </p>
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
                  <div className="text-center py-8 text-muted-foreground">
                    <AlertCircle className="h-8 w-8 mx-auto mb-2" />
                    <p>Select a completed session to mark or review attendance</p>
                  </div>
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
