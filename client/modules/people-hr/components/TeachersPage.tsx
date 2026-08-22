/**
 * Teachers Page — People & HR Module
 * Full CRUD with salary model display, class assignments, evaluations
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
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@shared/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@shared/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@shared/components/ui/table';
import { Users, Plus, Search, DollarSign, Edit, Trash2, GraduationCap, Calendar, Award, Star } from 'lucide-react';
import { formatAmount } from '@shared/lib/utils';

const TeacherSchema = z.object({
  full_name: z.string().min(2, 'Name is required'),
  phone: z.string().optional(),
  email: z.string().email().optional().or(z.literal('')),
  salary_type: z.enum(['fixed', 'per_skill', 'per_session', 'hybrid', 'per_level']),
  base_salary: z.string().optional(),
  specialization: z.string().optional(),
  qualification: z.string().optional(),
  contract_type: z.enum(['monthly', 'hourly', 'per_session']).optional(),
  joined_date: z.string().min(1, 'Joining date is required'),
});

type TeacherFormValues = z.infer<typeof TeacherSchema>;

interface Teacher {
  id: string;
  full_name: string;
  phone: string;
  email: string;
  salary_type: string;
  base_salary: number;
  status: string;
  specialization: string;
  qualification: string;
  contract_type: string;
  joined_date: string;
  classes: number;
  students: number;
  performance_score: number;
}

const mockTeachers: Teacher[] = [
  { id: '1', full_name: 'Ahmad Karimi', phone: '+93 700 111 222', email: 'ahmad@toeflhouse.af', salary_type: 'hybrid', base_salary: 15000, status: 'active', specialization: 'TOEFL Writing', qualification: 'MA English Literature', contract_type: 'monthly', joined_date: '2024-03-15', classes: 3, students: 45, performance_score: 4.5 },
  { id: '2', full_name: 'Sarah Noori', phone: '+93 700 222 333', email: 'sarah@toeflhouse.af', salary_type: 'per_skill', base_salary: 0, status: 'active', specialization: 'IELTS Speaking', qualification: 'CELTA Certified', contract_type: 'monthly', joined_date: '2024-06-01', classes: 4, students: 52, performance_score: 4.8 },
  { id: '3', full_name: 'Karim Rahimi', phone: '+93 700 333 444', email: 'karim@toeflhouse.af', salary_type: 'fixed', base_salary: 25000, status: 'active', specialization: 'General English', qualification: 'BA Education', contract_type: 'monthly', joined_date: '2023-09-01', classes: 2, students: 20, performance_score: 4.2 },
  { id: '4', full_name: 'Fatima Ahmadi', phone: '+93 700 444 555', email: 'fatima@toeflhouse.af', salary_type: 'per_session', base_salary: 0, status: 'active', specialization: 'TOEFL Reading & Listening', qualification: 'MA Linguistics', contract_type: 'per_session', joined_date: '2025-01-10', classes: 5, students: 68, performance_score: 4.6 },
  { id: '5', full_name: 'Ali Hussaini', phone: '+93 700 555 666', email: 'ali@toeflhouse.af', salary_type: 'per_level', base_salary: 0, status: 'on_leave', specialization: 'Business English', qualification: 'MBA', contract_type: 'monthly', joined_date: '2024-01-20', classes: 3, students: 35, performance_score: 4.0 },
];

const salaryTypeLabels: Record<string, string> = {
  fixed: 'Fixed Salary',
  per_skill: 'Per Skill',
  per_session: 'Per Session',
  hybrid: 'Hybrid',
  per_level: 'Per Level',
};

const salaryTypeColors: Record<string, string> = {
  fixed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
  per_skill: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100',
  per_session: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
  hybrid: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100',
  per_level: 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-100',
};

export function TeachersPage() {
  const [teachers, setTeachers] = useState(mockTeachers);
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [isAddDialogOpen, setIsAddDialogOpen] = useState(false);
  const [selectedTeacher, setSelectedTeacher] = useState<Teacher | null>(null);

  const filtered = teachers.filter((t) => {
    const matchesSearch = searchQuery === '' ||
      t.full_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      t.specialization.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesStatus = statusFilter === 'all' || t.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const { register, handleSubmit, reset, formState: { errors } } = useForm<TeacherFormValues>({
    resolver: zodResolver(TeacherSchema),
    defaultValues: { salary_type: 'fixed', contract_type: 'monthly' },
  });

  const onSubmit = (data: TeacherFormValues) => {
    const newTeacher: Teacher = {
      id: String(teachers.length + 1),
      full_name: data.full_name,
      phone: data.phone || '',
      email: data.email || '',
      salary_type: data.salary_type,
      base_salary: Number(data.base_salary) || 0,
      status: 'active',
      specialization: data.specialization || '',
      qualification: data.qualification || '',
      contract_type: data.contract_type || 'monthly',
      joined_date: data.joined_date,
      classes: 0,
      students: 0,
      performance_score: 0,
    };
    setTeachers([newTeacher, ...teachers]);
    setIsAddDialogOpen(false);
    reset();
  };

  const stats = {
    total: teachers.length,
    active: teachers.filter((t) => t.status === 'active').length,
    onLeave: teachers.filter((t) => t.status === 'on_leave').length,
    totalStudents: teachers.reduce((sum, t) => sum + t.students, 0),
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <Users className="h-8 w-8" />
            Teachers
          </h1>
          <p className="text-muted-foreground">Manage teaching staff, assignments, and payroll</p>
        </div>
        <Dialog open={isAddDialogOpen} onOpenChange={setIsAddDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="h-4 w-4 me-2" />
              Add Teacher
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-lg">
            <DialogHeader>
              <DialogTitle>Add New Teacher</DialogTitle>
              <DialogDescription>Register a new teacher in the system</DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2 col-span-2">
                  <Label>Full Name *</Label>
                  <Input placeholder="Teacher's full name" {...register('full_name')} />
                  {errors.full_name && <p className="text-xs text-destructive">{errors.full_name.message}</p>}
                </div>
                <div className="space-y-2">
                  <Label>Phone</Label>
                  <Input placeholder="+93 700 000 000" {...register('phone')} />
                </div>
                <div className="space-y-2">
                  <Label>Email</Label>
                  <Input type="email" {...register('email')} />
                </div>
                <div className="space-y-2">
                  <Label>Salary Type *</Label>
                  <Select defaultValue="fixed" onValueChange={(v) => register('salary_type').onChange({ target: { value: v } })}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="fixed">Fixed Salary</SelectItem>
                      <SelectItem value="per_skill">Per Skill</SelectItem>
                      <SelectItem value="per_session">Per Session</SelectItem>
                      <SelectItem value="hybrid">Hybrid (Base + Skills)</SelectItem>
                      <SelectItem value="per_level">Per Level</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Base Salary (AFN)</Label>
                  <Input type="number" placeholder="0" {...register('base_salary')} />
                </div>
                <div className="space-y-2">
                  <Label>Specialization</Label>
                  <Input placeholder="e.g. TOEFL Writing" {...register('specialization')} />
                </div>
                <div className="space-y-2">
                  <Label>Qualification</Label>
                  <Input placeholder="e.g. MA English" {...register('qualification')} />
                </div>
                <div className="space-y-2">
                  <Label>Contract Type</Label>
                  <Select defaultValue="monthly" onValueChange={(v) => register('contract_type').onChange({ target: { value: v } })}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="monthly">Monthly</SelectItem>
                      <SelectItem value="hourly">Hourly</SelectItem>
                      <SelectItem value="per_session">Per Session</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Joined Date *</Label>
                  <Input type="date" {...register('joined_date')} />
                  {errors.joined_date && <p className="text-xs text-destructive">{errors.joined_date.message}</p>}
                </div>
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => { setIsAddDialogOpen(false); reset(); }}>Cancel</Button>
                <Button type="submit">Add Teacher</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Stats */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card><CardContent className="pt-6"><div className="text-2xl font-bold">{stats.total}</div><p className="text-xs text-muted-foreground">Total Teachers</p></CardContent></Card>
        <Card><CardContent className="pt-6"><div className="text-2xl font-bold text-green-600">{stats.active}</div><p className="text-xs text-muted-foreground">Active</p></CardContent></Card>
        <Card><CardContent className="pt-6"><div className="text-2xl font-bold text-orange-600">{stats.onLeave}</div><p className="text-xs text-muted-foreground">On Leave</p></CardContent></Card>
        <Card><CardContent className="pt-6"><div className="text-2xl font-bold">{stats.totalStudents}</div><p className="text-xs text-muted-foreground">Total Students</p></CardContent></Card>
      </div>

      {/* Search & Filter */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-4 flex-wrap">
            <div className="flex-1 min-w-[200px] relative">
              <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input placeholder="Search by name or specialization..." className="ps-10" value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} />
            </div>
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[160px]"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="on_leave">On Leave</SelectItem>
                <SelectItem value="inactive">Inactive</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Specialization</TableHead>
                <TableHead>Salary Model</TableHead>
                <TableHead className="text-center">Classes</TableHead>
                <TableHead className="text-center">Students</TableHead>
                <TableHead className="text-center">Rating</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-end">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((teacher) => (
                <TableRow key={teacher.id}>
                  <TableCell>
                    <div>
                      <p className="font-medium">{teacher.full_name}</p>
                      <p className="text-xs text-muted-foreground">{teacher.phone}</p>
                    </div>
                  </TableCell>
                  <TableCell className="text-muted-foreground">{teacher.specialization || '—'}</TableCell>
                  <TableCell>
                    <Badge className={salaryTypeColors[teacher.salary_type]}>
                      {salaryTypeLabels[teacher.salary_type]}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-center">{teacher.classes}</TableCell>
                  <TableCell className="text-center">{teacher.students}</TableCell>
                  <TableCell className="text-center">
                    <div className="flex items-center justify-center gap-1">
                      <Star className="h-3 w-3 fill-yellow-400 text-yellow-400" />
                      <span className="text-sm font-medium">{teacher.performance_score}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant={teacher.status === 'active' ? 'default' : teacher.status === 'on_leave' ? 'secondary' : 'destructive'}>
                      {teacher.status.replace('_', ' ')}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-end">
                    <div className="flex items-center justify-end gap-1">
                      <Button variant="ghost" size="icon" onClick={() => setSelectedTeacher(teacher)}>
                        <DollarSign className="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="icon"><Edit className="h-4 w-4" /></Button>
                      <Button variant="ghost" size="icon" className="text-destructive" onClick={() => setTeachers(teachers.filter(t => t.id !== teacher.id))}>
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {/* Teacher Salary Detail Dialog */}
      <Dialog open={!!selectedTeacher} onOpenChange={() => setSelectedTeacher(null)}>
        <DialogContent className="max-w-lg">
          {selectedTeacher && (
            <>
              <DialogHeader>
                <DialogTitle>{selectedTeacher.full_name}</DialogTitle>
                <DialogDescription>Salary & Performance Details</DialogDescription>
              </DialogHeader>
              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <p className="text-sm text-muted-foreground">Salary Model</p>
                    <Badge className={salaryTypeColors[selectedTeacher.salary_type]}>
                      {salaryTypeLabels[selectedTeacher.salary_type]}
                    </Badge>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Base Salary</p>
                    <p className="font-medium">{selectedTeacher.base_salary > 0 ? `${formatAmount(selectedTeacher.base_salary)} AFN` : 'N/A'}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Classes</p>
                    <p className="font-medium">{selectedTeacher.classes}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Total Students</p>
                    <p className="font-medium">{selectedTeacher.students}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Specialization</p>
                    <p className="font-medium">{selectedTeacher.specialization || '—'}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Qualification</p>
                    <p className="font-medium">{selectedTeacher.qualification || '—'}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Contract</p>
                    <p className="font-medium capitalize">{selectedTeacher.contract_type}</p>
                  </div>
                  <div>
                    <p className="text-sm text-muted-foreground">Joined</p>
                    <p className="font-medium">{selectedTeacher.joined_date}</p>
                  </div>
                </div>

                <div className="border-t pt-4">
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-medium flex items-center gap-1"><Award className="h-4 w-4" /> Performance</span>
                    <span className="text-sm font-bold">{selectedTeacher.performance_score}/5.0</span>
                  </div>
                  <div className="w-full bg-muted rounded-full h-2">
                    <div className="h-2 rounded-full bg-yellow-500" style={{ width: `${(selectedTeacher.performance_score / 5) * 100}%` }} />
                  </div>
                </div>

                <div className="border-t pt-4">
                  <h4 className="text-sm font-medium mb-2">Recent Evaluations</h4>
                  <div className="space-y-2">
                    <div className="flex items-center justify-between p-2 rounded border text-sm">
                      <span>Classroom observation</span>
                      <span className="font-medium">4.5/5</span>
                    </div>
                    <div className="flex items-center justify-between p-2 rounded border text-sm">
                      <span>Student satisfaction survey</span>
                      <span className="font-medium">4.8/5</span>
                    </div>
                  </div>
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setSelectedTeacher(null)}>Close</Button>
                <Button>Process Salary</Button>
              </DialogFooter>
            </>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
