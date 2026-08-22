/**
 * Funding & Impact Page — Funding Module
 * Donor management, campaign tracking, donation recording, scholarship awards
 */

import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Input } from '@shared/components/ui/input';
import { Label } from '@shared/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@shared/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@shared/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@shared/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@shared/components/ui/select';
import {
  Heart, Plus, Users, Target, Award, TrendingUp, DollarSign,
  HandHeart, BookOpen, Star,
} from 'lucide-react';
import { formatAmount } from '@shared/lib/utils';

const DonationSchema = z.object({
  donor_name: z.string().min(2, 'Donor name is required'),
  amount: z.string().min(1, 'Amount is required'),
  date: z.string().min(1, 'Date is required'),
  restricted: z.boolean().optional(),
  restriction_note: z.string().optional(),
});

const ScholarshipAwardSchema = z.object({
  student_name: z.string().min(2, 'Student is required'),
  amount: z.string().min(1, 'Amount is required'),
  semester: z.string().optional(),
  notes: z.string().optional(),
});

type DonationFormValues = z.infer<typeof DonationSchema>;
type AwardFormValues = z.infer<typeof ScholarshipAwardSchema>;

interface Campaign {
  id: string;
  name: string;
  target: number;
  raised: number;
  status: string;
  donors: number;
}

interface Donor {
  id: string;
  name: string;
  type: string;
  total_donated: number;
  donations_count: number;
  country: string;
}

const campaigns: Campaign[] = [
  { id: '1', name: 'Winter Scholarship Fund', target: 500000, raised: 350000, status: 'active', donors: 12 },
  { id: '2', name: 'New Library Books', target: 200000, raised: 200000, status: 'completed', donors: 8 },
  { id: '3', name: 'Teacher Training Program', target: 300000, raised: 125000, status: 'active', donors: 5 },
  { id: '4', name: 'Computer Lab Equipment', target: 400000, raised: 80000, status: 'active', donors: 3 },
];

const donors: Donor[] = [
  { id: '1', name: 'Afghan Education Foundation', type: 'ngo', total_donated: 250000, donations_count: 4, country: 'Afghanistan' },
  { id: '2', name: 'Dr. Ahmad Wali', type: 'individual', total_donated: 150000, donations_count: 6, country: 'Germany' },
  { id: '3', name: 'Global Learning Initiative', type: 'organization', total_donated: 200000, donations_count: 2, country: 'USA' },
  { id: '4', name: 'Kabul Business Association', type: 'organization', total_donated: 100000, donations_count: 3, country: 'Afghanistan' },
  { id: '5', name: 'Fatima Zahra Trust', type: 'ngo', total_donated: 75000, donations_count: 5, country: 'UK' },
];

const scholarships = [
  { id: '1', name: 'Merit Scholarship', budget: 200000, allocated: 145000, awards: 12, status: 'active' },
  { id: '2', name: 'Need-Based Aid', budget: 300000, allocated: 220000, awards: 28, status: 'active' },
  { id: '3', name: "Women's Education Fund", budget: 150000, allocated: 150000, awards: 15, status: 'exhausted' },
];

const impactMetrics = [
  { name: 'Students Graduated', current: 245, target: 300, category: 'academic' },
  { name: 'Scholarships Awarded', current: 55, target: 80, category: 'social' },
  { name: 'Employment Rate (%)', current: 78, target: 85, category: 'economic' },
  { name: 'Female Enrollment (%)', current: 42, target: 50, category: 'demographic' },
];

const donorTypeColors: Record<string, string> = {
  individual: 'bg-blue-100 text-blue-800',
  organization: 'bg-green-100 text-green-800',
  ngo: 'bg-purple-100 text-purple-800',
  government: 'bg-orange-100 text-orange-800',
};

export function FundingPage() {
  const [isDonationOpen, setIsDonationOpen] = useState(false);
  const [isAwardOpen, setIsAwardOpen] = useState(false);

  const donationForm = useForm<DonationFormValues>({
    resolver: zodResolver(DonationSchema),
    defaultValues: { date: new Date().toISOString().split('T')[0] },
  });

  const awardForm = useForm<AwardFormValues>({
    resolver: zodResolver(ScholarshipAwardSchema),
  });

  const totalRaised = campaigns.reduce((s, c) => s + c.raised, 0);
  const totalTarget = campaigns.reduce((s, c) => s + c.target, 0);
  const totalDonors = donors.length;
  const totalScholarships = scholarships.reduce((s, sc) => s + sc.awards, 0);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <Heart className="h-8 w-8" />
            Funding & Impact
          </h1>
          <p className="text-muted-foreground">Manage donors, campaigns, scholarships, and impact reporting</p>
        </div>
        <div className="flex gap-2">
          <Dialog open={isDonationOpen} onOpenChange={setIsDonationOpen}>
            <DialogTrigger asChild>
              <Button variant="outline">
                <HandHeart className="h-4 w-4 me-2" /> Record Donation
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Record Donation</DialogTitle>
                <DialogDescription>Log a new donation from a donor</DialogDescription>
              </DialogHeader>
              <form onSubmit={donationForm.handleSubmit(() => setIsDonationOpen(false))} className="space-y-4">
                <div className="space-y-2">
                  <Label>Donor Name *</Label>
                  <Input placeholder="Search donor..." {...donationForm.register('donor_name')} />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Amount (AFN) *</Label>
                    <Input type="number" {...donationForm.register('amount')} />
                  </div>
                  <div className="space-y-2">
                    <Label>Date *</Label>
                    <Input type="date" {...donationForm.register('date')} />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Campaign</Label>
                  <Select>
                    <SelectTrigger><SelectValue placeholder="Assign to campaign (optional)" /></SelectTrigger>
                    <SelectContent>
                      {campaigns.filter(c => c.status === 'active').map(c => (
                        <SelectItem key={c.id} value={c.id}>{c.name}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Restriction Note</Label>
                  <Input placeholder="Any restrictions on use of funds..." {...donationForm.register('restriction_note')} />
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" onClick={() => setIsDonationOpen(false)}>Cancel</Button>
                  <Button type="submit">Record Donation</Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>

          <Dialog open={isAwardOpen} onOpenChange={setIsAwardOpen}>
            <DialogTrigger asChild>
              <Button>
                <Award className="h-4 w-4 me-2" /> Award Scholarship
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Award Scholarship</DialogTitle>
                <DialogDescription>Grant a scholarship to a student — automatically updates their tuition</DialogDescription>
              </DialogHeader>
              <form onSubmit={awardForm.handleSubmit(() => setIsAwardOpen(false))} className="space-y-4">
                <div className="space-y-2">
                  <Label>Scholarship Fund</Label>
                  <Select>
                    <SelectTrigger><SelectValue placeholder="Select fund" /></SelectTrigger>
                    <SelectContent>
                      {scholarships.filter(s => s.status === 'active').map(s => (
                        <SelectItem key={s.id} value={s.id}>{s.name} ({formatAmount(s.budget - s.allocated)} AFN remaining)</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label>Student *</Label>
                  <Input placeholder="Search student..." {...awardForm.register('student_name')} />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Amount (AFN) *</Label>
                    <Input type="number" {...awardForm.register('amount')} />
                  </div>
                  <div className="space-y-2">
                    <Label>Semester</Label>
                    <Input placeholder="e.g. Fall 2026" {...awardForm.register('semester')} />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Notes</Label>
                  <Input placeholder="Award justification..." {...awardForm.register('notes')} />
                </div>
                <div className="bg-blue-50 dark:bg-blue-950 rounded-lg p-3 text-sm">
                  <p className="font-medium text-blue-800 dark:text-blue-200">ℹ️ Auto-tuition update</p>
                  <p className="text-blue-700 dark:text-blue-300 text-xs mt-1">
                    This award will automatically increase the student's scholarshipPercent and recompute their net tuition via the TuitionCalculationService pipeline (02 §6).
                  </p>
                </div>
                <DialogFooter>
                  <Button type="button" variant="outline" onClick={() => setIsAwardOpen(false)}>Cancel</Button>
                  <Button type="submit"><Award className="h-4 w-4 me-2" /> Award Scholarship</Button>
                </DialogFooter>
              </form>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      {/* Stats */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Raised</CardTitle>
            <DollarSign className="h-5 w-5 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatAmount(totalRaised)} AFN</div>
            <p className="text-xs text-muted-foreground">{Math.round(totalRaised / totalTarget * 100)}% of target</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Campaigns</CardTitle>
            <Target className="h-5 w-5 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{campaigns.filter(c => c.status === 'active').length}</div>
            <p className="text-xs text-muted-foreground">{totalDonors} total donors</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Scholarships</CardTitle>
            <Award className="h-5 w-5 text-purple-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalScholarships}</div>
            <p className="text-xs text-muted-foreground">awards granted</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Graduation Rate</CardTitle>
            <TrendingUp className="h-5 w-5 text-emerald-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">82%</div>
            <p className="text-xs text-muted-foreground">+5% from last year</p>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="campaigns">
        <TabsList>
          <TabsTrigger value="campaigns">Campaigns</TabsTrigger>
          <TabsTrigger value="donors">Donors</TabsTrigger>
          <TabsTrigger value="scholarships">Scholarships</TabsTrigger>
          <TabsTrigger value="impact">Impact</TabsTrigger>
        </TabsList>

        {/* Campaigns */}
        <TabsContent value="campaigns">
          <div className="grid gap-4 md:grid-cols-2">
            {campaigns.map((campaign) => {
              const progress = Math.round((campaign.raised / campaign.target) * 100);
              return (
                <Card key={campaign.id}>
                  <CardHeader className="pb-3">
                    <div className="flex items-center justify-between">
                      <CardTitle className="text-base">{campaign.name}</CardTitle>
                      <Badge variant={campaign.status === 'completed' ? 'secondary' : 'default'}>{campaign.status}</Badge>
                    </div>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    <div className="flex justify-between text-sm">
                      <span className="text-muted-foreground">{formatAmount(campaign.raised)} raised</span>
                      <span className="font-medium">{formatAmount(campaign.target)} AFN</span>
                    </div>
                    <div className="w-full bg-muted rounded-full h-3">
                      <div
                        className={`h-3 rounded-full transition-all ${progress >= 100 ? 'bg-green-500' : progress > 60 ? 'bg-blue-500' : 'bg-yellow-500'}`}
                        style={{ width: `${Math.min(100, progress)}%` }}
                      />
                    </div>
                    <div className="flex justify-between text-xs text-muted-foreground">
                      <span>{progress}% funded</span>
                      <span>{campaign.donors} donors</span>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </TabsContent>

        {/* Donors */}
        <TabsContent value="donors">
          <Card>
            <CardContent className="pt-6">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Donor</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Country</TableHead>
                    <TableHead className="text-center">Donations</TableHead>
                    <TableHead className="text-end">Total Donated</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {donors.map((donor) => (
                    <TableRow key={donor.id}>
                      <TableCell className="font-medium">{donor.name}</TableCell>
                      <TableCell>
                        <Badge className={donorTypeColors[donor.type]}>{donor.type}</Badge>
                      </TableCell>
                      <TableCell className="text-muted-foreground">{donor.country}</TableCell>
                      <TableCell className="text-center">{donor.donations_count}</TableCell>
                      <TableCell className="text-end font-mono font-medium">{formatAmount(donor.total_donated)} AFN</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Scholarships */}
        <TabsContent value="scholarships">
          <div className="grid gap-4 md:grid-cols-3">
            {scholarships.map((sc) => {
              const utilization = Math.round((sc.allocated / sc.budget) * 100);
              return (
                <Card key={sc.id}>
                  <CardHeader className="pb-3">
                    <div className="flex items-center justify-between">
                      <CardTitle className="text-base flex items-center gap-2">
                        <BookOpen className="h-4 w-4" />
                        {sc.name}
                      </CardTitle>
                      <Badge variant={sc.status === 'active' ? 'default' : 'destructive'}>{sc.status}</Badge>
                    </div>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    <div className="flex justify-between text-sm">
                      <span className="text-muted-foreground">Allocated</span>
                      <span className="font-medium">{formatAmount(sc.allocated)} / {formatAmount(sc.budget)} AFN</span>
                    </div>
                    <div className="w-full bg-muted rounded-full h-2">
                      <div className={`h-2 rounded-full ${utilization >= 100 ? 'bg-red-500' : 'bg-purple-500'}`} style={{ width: `${Math.min(100, utilization)}%` }} />
                    </div>
                    <div className="flex justify-between text-xs text-muted-foreground">
                      <span>{sc.awards} awards</span>
                      <span>{utilization}% utilized</span>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>
        </TabsContent>

        {/* Impact */}
        <TabsContent value="impact">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2"><Star className="h-5 w-5" /> Impact Metrics</CardTitle>
              <CardDescription>Track progress against organizational goals</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {impactMetrics.map((metric, i) => {
                const progress = Math.round((metric.current / metric.target) * 100);
                return (
                  <div key={i} className="space-y-2">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <span className="font-medium">{metric.name}</span>
                        <Badge variant="outline" className="text-xs">{metric.category}</Badge>
                      </div>
                      <span className="text-sm text-muted-foreground">{metric.current} / {metric.target}</span>
                    </div>
                    <div className="w-full bg-muted rounded-full h-3">
                      <div
                        className={`h-3 rounded-full ${progress >= 80 ? 'bg-green-500' : progress >= 50 ? 'bg-yellow-500' : 'bg-red-500'}`}
                        style={{ width: `${progress}%` }}
                      />
                    </div>
                    <p className="text-xs text-muted-foreground text-end">{progress}% of target</p>
                  </div>
                );
              })}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
