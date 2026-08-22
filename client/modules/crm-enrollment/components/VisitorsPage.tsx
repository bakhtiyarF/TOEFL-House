/**
 * Visitors Page — CRM & Enrollment Module
 * Pipeline visualization, follow-up tracking, visitor → student conversion
 */

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Input } from '@shared/components/ui/input';
import { Label } from '@shared/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@shared/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@shared/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@shared/components/ui/select';
import {
  UserPlus, Plus, Search, Phone, ArrowRight, CheckCircle2, XCircle,
  Clock, MessageSquare, GraduationCap, AlertCircle, TrendingUp, Filter,
} from 'lucide-react';
import { useVisitors, useConvertVisitor } from '../hooks/useCrm';
import { crmApi } from '../api';

const VisitorSchema = z.object({
  full_name: z.string().min(2, 'Name is required'),
  phone: z.string().min(5, 'Phone is required'),
  email: z.string().email().optional().or(z.literal('')),
  gender: z.enum(['male', 'female']).optional(),
  source: z.string().optional(),
  interested_course: z.string().optional(),
  notes: z.string().optional(),
});

type VisitorFormValues = z.infer<typeof VisitorSchema>;

interface Visitor {
  id: string;
  serial_no: string;
  full_name: string;
  phone: string;
  stage: string;
  source: string;
  visit_date: string;
  status: string;
  interested_course: string;
  placement_score: number | null;
  placement_fee_paid: boolean;
  next_contact_date: string | null;
  assigned_to: string;
}

const stages = ['lead', 'inquiry', 'follow_up', 'placement_booking', 'placement_completed', 'registration', 'enrollment'] as const;

const stageColors: Record<string, string> = {
  lead: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
  inquiry: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  follow_up: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  placement_booking: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
  placement_completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  registration: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
  enrollment: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
  lost: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

const mockVisitors: Visitor[] = [
  { id: '1', serial_no: 'V-A1B2C3', full_name: 'Sara Mohammadi', phone: '+93 700 111 111', stage: 'lead', source: 'friend', visit_date: '2026-08-20', status: 'visited', interested_course: 'General English', placement_score: null, placement_fee_paid: false, next_contact_date: '2026-08-25', assigned_to: 'Counselor' },
  { id: '2', serial_no: 'V-D4E5F6', full_name: 'Hassan Rezai', phone: '+93 700 222 222', stage: 'follow_up', source: 'social', visit_date: '2026-08-19', status: 'visited', interested_course: 'TOEFL Prep', placement_score: null, placement_fee_paid: false, next_contact_date: '2026-08-23', assigned_to: 'Counselor' },
  { id: '3', serial_no: 'V-G7H8I9', full_name: 'Maryam Karimi', phone: '+93 700 333 333', stage: 'placement_completed', source: 'ads', visit_date: '2026-08-18', status: 'visited', interested_course: 'General English', placement_score: 78, placement_fee_paid: true, next_contact_date: null, assigned_to: 'Reception' },
  { id: '4', serial_no: 'V-J1K2L3', full_name: 'Reza Ahmadi', phone: '+93 700 444 444', stage: 'inquiry', source: 'referral', visit_date: '2026-08-18', status: 'visited', interested_course: 'IELTS', placement_score: null, placement_fee_paid: false, next_contact_date: '2026-08-24', assigned_to: 'Counselor' },
  { id: '5', serial_no: 'V-M4N5O6', full_name: 'Nadia Faizi', phone: '+93 700 555 555', stage: 'registration', source: 'organic', visit_date: '2026-08-17', status: 'registered', interested_course: 'General English', placement_score: 85, placement_fee_paid: true, next_contact_date: null, assigned_to: 'Reception' },
  { id: '6', serial_no: 'V-P7Q8R9', full_name: 'Farid Noor', phone: '+93 700 666 666', stage: 'placement_booking', source: 'friend', visit_date: '2026-08-21', status: 'visited', interested_course: 'TOEFL Prep', placement_score: null, placement_fee_paid: false, next_contact_date: '2026-08-22', assigned_to: 'Reception' },
  { id: '7', serial_no: 'V-S1T2U3', full_name: 'Zainab Hussaini', phone: '+93 700 777 777', stage: 'follow_up', source: 'social', visit_date: '2026-08-16', status: 'visited', interested_course: 'General English', placement_score: null, placement_fee_paid: false, next_contact_date: '2026-08-22', assigned_to: 'Counselor' },
  { id: '8', serial_no: 'V-V4W5X6', full_name: 'Omid Safi', phone: '+93 700 888 888', stage: 'enrollment', source: 'ads', visit_date: '2026-08-15', status: 'registered', interested_course: 'IELTS Advanced', placement_score: 92, placement_fee_paid: true, next_contact_date: null, assigned_to: 'Reception' },
];

export function VisitorsPage() {
  const { data: visitorsData = [], isLoading } = useVisitors();
  const convertVisitor = useConvertVisitor();

  const [searchQuery, setSearchQuery] = useState('');
  const [stageFilter, setStageFilter] = useState('all');
  const [isAddDialogOpen, setIsAddDialogOpen] = useState(false);
  const [isConvertDialogOpen, setIsConvertDialogOpen] = useState(false);
  const [selectedVisitor, setSelectedVisitor] = useState<Visitor | null>(null);

  // Use live data or fallback
  const liveVisitors = (Array.isArray(visitorsData) ? visitorsData : []) as Visitor[];
  const visitors: Visitor[] = liveVisitors.length > 0 ? liveVisitors : mockVisitors;

  const filtered = visitors.filter((v) => {
    const matchesSearch = searchQuery === '' ||
      v.full_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      v.phone.includes(searchQuery);
    const matchesStage = stageFilter === 'all' || v.stage === stageFilter;
    return matchesSearch && matchesStage;
  });

  const pipelineCounts = stages.reduce((acc, stage) => {
    acc[stage] = visitors.filter((v) => v.stage === stage).length;
    return acc;
  }, {} as Record<string, number>);

  const { register, handleSubmit, reset, formState: { errors } } = useForm<VisitorFormValues>({
    resolver: zodResolver(VisitorSchema),
  });

  const onSubmit = (data: VisitorFormValues) => {
    crmApi.visitors.create({
      full_name: data.full_name,
      phone: data.phone,
      email: data.email || undefined,
      source: data.source,
      interested_course: data.interested_course,
      notes: data.notes,
    }).then(() => {
      setIsAddDialogOpen(false);
      reset();
      // TanStack will refetch via invalidation in real hooks
    });
  };

  const canConvert = (visitor: Visitor) => {
    return visitor.placement_score !== null && visitor.placement_fee_paid && visitor.status !== 'registered';
  };

  const handleConvert = (visitor: Visitor, programVersionId?: string) => {
    if (!canConvert(visitor)) return;

    convertVisitor.mutate(visitor.id, {
      onSuccess: (result: any) => {
        setIsConvertDialogOpen(false);
        setSelectedVisitor(null);
        alert(`Converted successfully! New student: ${result?.student_code || result?.student_id || 'created'}`);
        // In production: window.location or query invalidation would refresh Students list
      },
    });
  };

  const conversionRate = visitors.length > 0
    ? Math.round((visitors.filter((v) => v.status === 'registered' || v.stage === 'enrollment').length / visitors.length) * 100)
    : 0;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <UserPlus className="h-8 w-8" />
            Visitors & Leads
          </h1>
          <p className="text-muted-foreground">Manage visitor pipeline and enrollment conversion</p>
        </div>
        <Dialog open={isAddDialogOpen} onOpenChange={setIsAddDialogOpen}>
          <DialogTrigger asChild>
            <Button><Plus className="h-4 w-4 me-2" /> Add Visitor</Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Add New Visitor</DialogTitle>
              <DialogDescription>Log a new visitor/lead</DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2 col-span-2">
                  <Label>Full Name *</Label>
                  <Input placeholder="Visitor's name" {...register('full_name')} />
                  {errors.full_name && <p className="text-xs text-destructive">{errors.full_name.message}</p>}
                </div>
                <div className="space-y-2">
                  <Label>Phone *</Label>
                  <Input placeholder="+93 700 000 000" {...register('phone')} />
                  {errors.phone && <p className="text-xs text-destructive">{errors.phone.message}</p>}
                </div>
                <div className="space-y-2">
                  <Label>Email</Label>
                  <Input type="email" {...register('email')} />
                </div>
                <div className="space-y-2">
                  <Label>Source</Label>
                  <Select onValueChange={(v) => register('source').onChange({ target: { value: v } })}>
                    <SelectTrigger><SelectValue placeholder="How did they find us?" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="friend">Friend Referral</SelectItem>
                      <SelectItem value="social">Social Media</SelectItem>
                      <SelectItem value="ads">Advertisement</SelectItem>
                      <SelectItem value="referral">Partner Referral</SelectItem>
                      <SelectItem value="organic">Walk-in / Organic</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Interested Course</Label>
                  <Select onValueChange={(v) => register('interested_course').onChange({ target: { value: v } })}>
                    <SelectTrigger><SelectValue placeholder="Select course" /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="General English">General English</SelectItem>
                      <SelectItem value="TOEFL Prep">TOEFL Preparation</SelectItem>
                      <SelectItem value="IELTS">IELTS</SelectItem>
                      <SelectItem value="Business English">Business English</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2 col-span-2">
                  <Label>Notes</Label>
                  <Input placeholder="Additional notes..." {...register('notes')} />
                </div>
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => { setIsAddDialogOpen(false); reset(); }}>Cancel</Button>
                <Button type="submit">Add Visitor</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Pipeline Summary */}
      <div className="grid gap-2 grid-cols-2 md:grid-cols-4 lg:grid-cols-7">
        {stages.map((stage) => (
          <Card key={stage} className="text-center p-3 cursor-pointer hover:bg-accent transition-colors" onClick={() => setStageFilter(stageFilter === stage ? 'all' : stage)}>
            <p className="text-2xl font-bold">{pipelineCounts[stage] || 0}</p>
            <p className="text-xs text-muted-foreground capitalize">{stage.replace(/_/g, ' ')}</p>
          </Card>
        ))}
      </div>

      {/* Conversion Stats */}
      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-2">
              <TrendingUp className="h-5 w-5 text-green-600" />
              <div>
                <p className="text-2xl font-bold">{conversionRate}%</p>
                <p className="text-xs text-muted-foreground">Conversion Rate</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-2">
              <MessageSquare className="h-5 w-5 text-blue-600" />
              <div>
                <p className="text-2xl font-bold">{visitors.filter(v => v.next_contact_date).length}</p>
                <p className="text-xs text-muted-foreground">Pending Follow-ups</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="flex items-center gap-2">
              <GraduationCap className="h-5 w-5 text-purple-600" />
              <div>
                <p className="text-2xl font-bold">{visitors.filter(v => v.placement_score !== null).length}</p>
                <p className="text-xs text-muted-foreground">Placement Completed</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Visitors Table */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-4 flex-wrap">
            <div className="flex-1 min-w-[200px] relative">
              <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input placeholder="Search visitors..." className="ps-10" value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} />
            </div>
            <Select value={stageFilter} onValueChange={setStageFilter}>
              <SelectTrigger className="w-[180px]">
                <Filter className="h-4 w-4 me-2" />
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Stages</SelectItem>
                {stages.map((s) => (
                  <SelectItem key={s} value={s}>{s.replace(/_/g, ' ')}</SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Serial</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>Source</TableHead>
                <TableHead>Course</TableHead>
                <TableHead>Stage</TableHead>
                <TableHead className="text-center">Placement</TableHead>
                <TableHead className="text-end">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filtered.map((visitor) => (
                <TableRow key={visitor.id}>
                  <TableCell className="font-mono text-xs">{visitor.serial_no}</TableCell>
                  <TableCell className="font-medium">{visitor.full_name}</TableCell>
                  <TableCell>
                    <div className="flex items-center gap-1 text-muted-foreground">
                      <Phone className="h-3 w-3" />
                      <span className="text-xs">{visitor.phone}</span>
                    </div>
                  </TableCell>
                  <TableCell className="capitalize text-sm">{visitor.source}</TableCell>
                  <TableCell className="text-sm">{visitor.interested_course}</TableCell>
                  <TableCell>
                    <Badge className={stageColors[visitor.stage]}>
                      {visitor.stage.replace(/_/g, ' ')}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-center">
                    {visitor.placement_score !== null ? (
                      <div className="flex items-center justify-center gap-1">
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                        <span className="text-sm font-medium">{visitor.placement_score}</span>
                      </div>
                    ) : (
                      <span className="text-muted-foreground text-xs">—</span>
                    )}
                  </TableCell>
                  <TableCell className="text-end">
                    <div className="flex items-center justify-end gap-1">
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => { setSelectedVisitor(visitor); setIsConvertDialogOpen(true); }}
                        disabled={!canConvert(visitor)}
                        className={canConvert(visitor) ? 'text-green-600' : ''}
                      >
                        <ArrowRight className="h-4 w-4 me-1" />
                        Convert
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {/* Conversion Dialog */}
      <Dialog open={isConvertDialogOpen} onOpenChange={setIsConvertDialogOpen}>
        <DialogContent>
          {selectedVisitor && (
            <>
              <DialogHeader>
                <DialogTitle className="flex items-center gap-2">
                  <GraduationCap className="h-5 w-5" />
                  Convert to Student
                </DialogTitle>
                <DialogDescription>
                  Review conversion readiness for {selectedVisitor.full_name}
                </DialogDescription>
              </DialogHeader>

              <div className="space-y-4">
                {/* Readiness Check */}
                <div className="space-y-2">
                  <h4 className="text-sm font-medium">Conversion Readiness</h4>
                  <div className="space-y-2">
                    <div className="flex items-center gap-2">
                      {selectedVisitor.placement_score !== null ? (
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                      ) : (
                        <XCircle className="h-4 w-4 text-red-600" />
                      )}
                      <span className="text-sm">
                        Placement test {selectedVisitor.placement_score !== null ? `completed (Score: ${selectedVisitor.placement_score})` : 'not completed'}
                      </span>
                    </div>
                    <div className="flex items-center gap-2">
                      {selectedVisitor.placement_fee_paid ? (
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                      ) : (
                        <XCircle className="h-4 w-4 text-red-600" />
                      )}
                      <span className="text-sm">
                        Placement fee {selectedVisitor.placement_fee_paid ? 'paid' : 'unpaid (300 AFN required)'}
                      </span>
                    </div>
                    <div className="flex items-center gap-2">
                      {selectedVisitor.status !== 'registered' ? (
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                      ) : (
                        <AlertCircle className="h-4 w-4 text-orange-600" />
                      )}
                      <span className="text-sm">
                        {selectedVisitor.status !== 'registered' ? 'Not yet converted' : 'Already converted'}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Enrollment Details */}
                {canConvert(selectedVisitor) && (
                  <div className="border-t pt-4 space-y-3">
                    <h4 className="text-sm font-medium">Enrollment Details</h4>
                    <div className="grid grid-cols-2 gap-3">
                      <div className="space-y-1">
                        <Label className="text-xs">Program</Label>
                        <Select defaultValue="General English" onValueChange={(v) => {
                          // store for conversion payload (simplified)
                          (window as any).__selectedProgram = v;
                        }}>
                          <SelectTrigger className="h-8"><SelectValue /></SelectTrigger>
                          <SelectContent>
                            <SelectItem value="General English">General English</SelectItem>
                            <SelectItem value="TOEFL Prep">TOEFL Preparation</SelectItem>
                            <SelectItem value="IELTS">IELTS</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-1">
                        <Label className="text-xs">Recommended Level</Label>
                        <Input className="h-8" defaultValue={
                          selectedVisitor.placement_score! >= 90 ? 'Advanced' :
                          selectedVisitor.placement_score! >= 70 ? 'Level 3' :
                          selectedVisitor.placement_score! >= 50 ? 'Level 2' : 'Level 1'
                        } readOnly />
                      </div>
                    </div>
                    <div className="text-xs text-muted-foreground">Program version will be resolved server-side for copy-on-write snapshot.</div>
                  </div>
                )}

                {!canConvert(selectedVisitor) && (
                  <div className="bg-destructive/10 rounded-lg p-3">
                    <div className="flex items-start gap-2">
                      <AlertCircle className="h-4 w-4 text-destructive mt-0.5" />
                      <div className="text-sm text-destructive">
                        <p className="font-medium">Cannot convert</p>
                        <p className="text-xs mt-1">
                          {selectedVisitor.status === 'registered'
                            ? 'This visitor has already been converted to a student.'
                            : 'Complete placement test and pay the placement fee before converting.'}
                        </p>
                      </div>
                    </div>
                  </div>
                )}
              </div>

              <DialogFooter>
                <Button variant="outline" onClick={() => { setIsConvertDialogOpen(false); setSelectedVisitor(null); }}>Cancel</Button>
                <Button
                  onClick={() => handleConvert(selectedVisitor)}
                  disabled={!canConvert(selectedVisitor)}
                >
                  <GraduationCap className="h-4 w-4 me-2" />
                  Convert to Student
                </Button>
              </DialogFooter>
            </>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
