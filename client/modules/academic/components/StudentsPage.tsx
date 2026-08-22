/**
 * Students Page — Academic Module
 * Full CRUD with forms, search, filters, and journey timeline
 */

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Input } from '@shared/components/ui/input';
import { Label } from '@shared/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@shared/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@shared/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@shared/components/ui/select';
import { GraduationCap, Plus, Search, Filter, Eye, Edit, Trash2, Phone, MapPin, User, Download } from 'lucide-react';
import { StudentJourneyTimeline } from './StudentJourneyTimeline';
import { useStudents, useCreateStudent, useEnrollStudent, useClasses, usePrograms, useProgramVersions } from '../hooks/useAcademic';
import { exportStudents } from '@shared/lib/export';

const StudentSchema = z.object({
  full_name: z.string().min(2, 'Name must be at least 2 characters'),
  phone: z.string().optional(),
  email: z.string().email('Invalid email').optional().or(z.literal('')),
  gender: z.enum(['male', 'female']).optional(),
  father_name: z.string().optional(),
  address_region: z.string().optional(),
  tazkira_no: z.string().optional(),
  whatsapp: z.string().optional(),
  dob: z.string().optional(),
  school_or_university: z.string().optional(),
  emergency_contact_name: z.string().optional(),
  emergency_contact_phone: z.string().optional(),
  discount_percent: z.string().optional(),
});

type StudentFormValues = z.infer<typeof StudentSchema>;

const mockStudents = [
  { id: '1', student_code: 'STU-2026-0001', full_name: 'Ahmad Rahimi', gender: 'male', phone: '+93 700 123 456', status: 'active', class_name: 'General English L3', father_name: 'Mohammad Rahim', registration_date: '2026-01-15', discount_percent: 0, branch_id: 'branch-1' },
  { id: '2', student_code: 'STU-2026-0002', full_name: 'Fatima Ahmadi', gender: 'female', phone: '+93 700 234 567', status: 'active', class_name: 'TOEFL Prep L2', father_name: 'Ali Ahmadi', registration_date: '2026-01-20', discount_percent: 10, branch_id: 'branch-1' },
  { id: '3', student_code: 'STU-2026-0003', full_name: 'Mohammad Karimi', gender: 'male', phone: '+93 700 345 678', status: 'graduated', class_name: 'IELTS Advanced', father_name: 'Hassan Karim', registration_date: '2025-09-01', discount_percent: 0, branch_id: 'branch-1' },
  { id: '4', student_code: 'STU-2026-0004', full_name: 'Zahra Noori', gender: 'female', phone: '+93 700 456 789', status: 'active', class_name: 'General English L1', father_name: 'Reza Noori', registration_date: '2026-02-10', discount_percent: 15, branch_id: 'branch-1' },
  { id: '5', student_code: 'STU-2026-0005', full_name: 'Ali Hussaini', gender: 'male', phone: '+93 700 567 890', status: 'inactive', class_name: 'General English L4', father_name: 'Karim Hussaini', registration_date: '2025-11-05', discount_percent: 0, branch_id: 'branch-1' },
  { id: '6', student_code: 'STU-2026-0006', full_name: 'Sara Mohammadi', gender: 'female', phone: '+93 700 678 901', status: 'active', class_name: 'TOEFL Prep L1', father_name: 'Abdullah Mohammadi', registration_date: '2026-03-01', discount_percent: 5, branch_id: 'branch-1' },
  { id: '7', student_code: 'STU-2026-0007', full_name: 'Hassan Rezai', gender: 'male', phone: '+93 700 789 012', status: 'active', class_name: 'General English L2', father_name: 'Nasir Rezai', registration_date: '2026-03-15', discount_percent: 0, branch_id: 'branch-1' },
  { id: '8', student_code: 'STU-2026-0008', full_name: 'Maryam Faizi', gender: 'female', phone: '+93 700 890 123', status: 'suspended', class_name: 'IELTS Advanced', father_name: 'Yousuf Faizi', registration_date: '2025-12-20', discount_percent: 20, branch_id: 'branch-1' },
];

const statusVariants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
  active: 'default',
  inactive: 'secondary',
  graduated: 'outline',
  suspended: 'destructive',
};

export function StudentsPage() {
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [isAddDialogOpen, setIsAddDialogOpen] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState<any>(null);
  const [isEnrollOpen, setIsEnrollOpen] = useState(false);
  const [enrollProgramId, setEnrollProgramId] = useState('');
  const [enrollVersionId, setEnrollVersionId] = useState('');

  const { data: studentsData = [] } = useStudents({ status: statusFilter === 'all' ? undefined : statusFilter });
  const createStudent = useCreateStudent();
  const enrollStudent = useEnrollStudent();
  const { data: classes = [] } = useClasses();
  const { data: programs = [] } = usePrograms();

  const { data: programVersions = [] } = useProgramVersions(enrollProgramId);

  // Pure live data only
  const liveStudents = Array.isArray(studentsData) ? studentsData : [];
  const students = liveStudents.filter((s: any) => {
    const matchesSearch = searchQuery === '' ||
      s.full_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      (s.student_code || '').toLowerCase().includes(searchQuery.toLowerCase()) ||
      (s.phone || '').includes(searchQuery);
    const matchesStatus = statusFilter === 'all' || s.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<StudentFormValues>({
    resolver: zodResolver(StudentSchema),
  });

  const onSubmit = (data: StudentFormValues) => {
    createStudent.mutate({
      full_name: data.full_name,
      phone: data.phone,
      email: data.email || undefined,
      gender: data.gender,
      father_name: data.father_name,
      address_region: data.address_region,
      tazkira_no: data.tazkira_no,
      whatsapp: data.whatsapp,
      dob: data.dob,
      discount_percent: Number(data.discount_percent) || 0,
      branch_id: 'branch-1', // TODO: from auth
    } as any, {
      onSuccess: () => {
        setIsAddDialogOpen(false);
        reset();
      }
    });
  };

  const handleDelete = (id: string) => {
    // TODO: real delete mutation
  };

  const handleEnroll = (studentId: string, classId: string) => {
    enrollStudent.mutate({
      studentId,
      data: {
        class_id: classId,
        program_version_id: enrollVersionId || undefined,
        enrollment_type: 'new',
      },
    }, {
      onSuccess: () => {
        setIsEnrollOpen(false);
        setEnrollProgramId('');
        setEnrollVersionId('');
      },
    });
  };

  const stats = {
    total: students.length,
    active: students.filter((s: any) => s.status === 'active').length,
    graduated: students.filter((s: any) => s.status === 'graduated').length,
    inactive: students.filter((s: any) => s.status !== 'active' && s.status !== 'graduated').length,
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <GraduationCap className="h-8 w-8" />
            Students
          </h1>
          <p className="text-muted-foreground">Manage student records and enrollment</p>
        </div>

        <div className="flex gap-2">
          <Button variant="outline" onClick={() => exportStudents(students as any)}>
            <Download className="h-4 w-4 me-2" />
            Export CSV
          </Button>
          <Dialog open={isAddDialogOpen} onOpenChange={setIsAddDialogOpen}>
            <DialogTrigger asChild>
              <Button>
                <Plus className="h-4 w-4 me-2" />
                Add Student
              </Button>
            </DialogTrigger>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>Register New Student</DialogTitle>
              <DialogDescription>
                Fill in the student's information to create a new record
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="full_name">Full Name *</Label>
                  <Input id="full_name" placeholder="Student's full name" {...register('full_name')} />
                  {errors.full_name && <p className="text-xs text-destructive">{errors.full_name.message}</p>}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="father_name">Father's Name</Label>
                  <Input id="father_name" placeholder="Father's name" {...register('father_name')} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="phone">Phone</Label>
                  <Input id="phone" placeholder="+93 700 000 000" {...register('phone')} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="whatsapp">WhatsApp</Label>
                  <Input id="whatsapp" placeholder="+93 700 000 000" {...register('whatsapp')} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input id="email" type="email" placeholder="student@example.com" {...register('email')} />
                  {errors.email && <p className="text-xs text-destructive">{errors.email.message}</p>}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="gender">Gender</Label>
                  <Select onValueChange={(v) => register('gender').onChange({ target: { value: v } })}>
                    <SelectTrigger><SelectValue placeholder="Select gender" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="male">Male</SelectItem>
                      <SelectItem value="female">Female</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="dob">Date of Birth</Label>
                  <Input id="dob" type="date" {...register('dob')} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="tazkira_no">Tazkira No.</Label>
                  <Input id="tazkira_no" placeholder="National ID number" {...register('tazkira_no')} />
                </div>
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="address_region">Address / Region</Label>
                  <Input id="address_region" placeholder="Address or region" {...register('address_region')} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="school_or_university">School / University</Label>
                  <Input id="school_or_university" placeholder="Current school or university" {...register('school_or_university')} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="discount_percent">Discount (%)</Label>
                  <Input id="discount_percent" type="number" min="0" max="100" placeholder="0" {...register('discount_percent')} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="emergency_contact_name">Emergency Contact Name</Label>
                  <Input id="emergency_contact_name" {...register('emergency_contact_name')} />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="emergency_contact_phone">Emergency Contact Phone</Label>
                  <Input id="emergency_contact_phone" {...register('emergency_contact_phone')} />
                </div>
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => { setIsAddDialogOpen(false); reset(); }}>
                  Cancel
                </Button>
                <Button type="submit">Register Student</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
        </div>
      </div>

      {/* Stats */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold">{stats.total}</div>
            <p className="text-xs text-muted-foreground">Total Students</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold text-green-600">{stats.active}</div>
            <p className="text-xs text-muted-foreground">Active</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold text-blue-600">{stats.graduated}</div>
            <p className="text-xs text-muted-foreground">Graduated</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-2xl font-bold text-orange-600">{stats.inactive}</div>
            <p className="text-xs text-muted-foreground">Inactive / Suspended</p>
          </CardContent>
        </Card>
      </div>

      {/* Search & Filter */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-4 flex-wrap">
            <div className="flex-1 min-w-[200px] relative">
              <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search by name, code, or phone..."
                className="ps-10"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[180px]">
                <Filter className="h-4 w-4 me-2" />
                <SelectValue placeholder="Filter by status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="inactive">Inactive</SelectItem>
                <SelectItem value="graduated">Graduated</SelectItem>
                <SelectItem value="suspended">Suspended</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Code</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Gender</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Class</TableHead>
                <TableHead>Discount</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-end">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {students.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                    No students found. {searchQuery && 'Try a different search term.'}
                  </TableCell>
                </TableRow>
              ) : (
                students.map((student: any) => (
                  <TableRow key={student.id}>
                    <TableCell className="font-mono text-xs">{student.student_code}</TableCell>
                    <TableCell className="font-medium">{student.full_name}</TableCell>
                    <TableCell className="capitalize">{student.gender}</TableCell>
                    <TableCell className="text-muted-foreground">{student.phone}</TableCell>
                    <TableCell>{student.class_name}</TableCell>
                    <TableCell>{student.discount_percent > 0 ? `${student.discount_percent}%` : '—'}</TableCell>
                    <TableCell>
                      <Badge variant={statusVariants[student.status]}>{student.status}</Badge>
                    </TableCell>
                    <TableCell className="text-end">
                      <div className="flex items-center justify-end gap-1">
                        <Button variant="ghost" size="icon" onClick={() => setSelectedStudent(student)}>
                          <Eye className="h-4 w-4" />
                        </Button>
                        <Button variant="ghost" size="icon" onClick={() => { setSelectedStudent(student); setIsEnrollOpen(true); }}>
                          Enroll
                        </Button>
                        <Button variant="ghost" size="icon" className="text-destructive" onClick={() => handleDelete(student.id)}>
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {/* Student Detail Dialog */}
      <Dialog open={!!selectedStudent} onOpenChange={() => setSelectedStudent(null)}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          {selectedStudent && (
            <>
              <DialogHeader>
                <DialogTitle className="flex items-center gap-2">
                  <User className="h-5 w-5" />
                  {selectedStudent.full_name}
                </DialogTitle>
                <DialogDescription>{selectedStudent.student_code}</DialogDescription>
              </DialogHeader>
              <div className="space-y-4">
                <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                  <div>
                    <p className="text-sm text-muted-foreground">Father's Name</p>
                    <p className="font-medium">{selectedStudent.father_name || '—'}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Gender</p>
                    <p className="font-medium capitalize">{selectedStudent.gender}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground flex items-center gap-1">
                      <Phone className="h-3 w-3" /> Phone
                    </p>
                    <p className="font-medium">{selectedStudent.phone}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Class</p>
                    <p className="font-medium">{selectedStudent.class_name}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Registration Date</p>
                    <p className="font-medium">{selectedStudent.registration_date}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Discount</p>
                    <p className="font-medium">{selectedStudent.discount_percent}%</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant={statusVariants[selectedStudent.status]}>{selectedStudent.status}</Badge>
                  <Button size="sm" variant="outline" onClick={() => { setIsEnrollOpen(true); }}>Enroll in Class</Button>
                </div>

                {/* Student Journey Timeline */}
                <div className="border-t pt-4">
                  <StudentJourneyTimeline studentName={selectedStudent.full_name} />
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setSelectedStudent(null)}>Close</Button>
                <Button>Edit Student</Button>
              </DialogFooter>
            </>
          )}
        </DialogContent>
      </Dialog>

      {/* Enroll Dialog */}
      <Dialog open={isEnrollOpen} onOpenChange={setIsEnrollOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Enroll {selectedStudent?.full_name}</DialogTitle>
            <DialogDescription>Select program (for snapshot) + class</DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div>
              <Label>Program (pins version + fee snapshot)</Label>
              <Select value={enrollProgramId} onValueChange={(v) => { setEnrollProgramId(v); setEnrollVersionId(''); }}>
                <SelectTrigger><SelectValue placeholder="Select program" /></SelectTrigger>
                <SelectContent>
                  {programs.length > 0 ? programs.map((p: any) => (
                    <SelectItem key={p.id} value={p.id}>{p.name}</SelectItem>
                  )) : <SelectItem value="">No programs</SelectItem>}
                </SelectContent>
              </Select>
            </div>
            {enrollProgramId && (
              <div>
                <Label>Program Version</Label>
                <Select value={enrollVersionId} onValueChange={setEnrollVersionId}>
                  <SelectTrigger><SelectValue placeholder="Select version" /></SelectTrigger>
                  <SelectContent>
                    {programVersions.length > 0 ? programVersions.map((v: any) => (
                      <SelectItem key={v.id} value={v.id}>{v.version_label || 'v' + (v.version_number || 1)}</SelectItem>
                    )) : <SelectItem value="">Default (latest)</SelectItem>}
                  </SelectContent>
                </Select>
              </div>
            )}
            <div>
              <Label>Class</Label>
              <Select onValueChange={(classId) => {
                if (selectedStudent) handleEnroll(selectedStudent.id, classId);
              }}>
                <SelectTrigger>
                  <SelectValue placeholder="Select class" />
                </SelectTrigger>
                <SelectContent>
                  {classes.filter((c: any) => c.status === 'active').map((cls: any) => (
                    <SelectItem key={cls.id} value={cls.id}>
                      {cls.name} — {cls.enrolled_count || 0}/{cls.capacity || 20}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => { setIsEnrollOpen(false); setEnrollProgramId(''); setEnrollVersionId(''); }}>Cancel</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
