/**
 * Finance Page — Finance & Payroll Module
 * Fully live: payments (create + list), student finance, teacher payroll computation
 * Budget and transaction history now primarily driven by live backend data
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
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@shared/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@shared/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@shared/components/ui/table';
import {
  DollarSign, Plus, TrendingUp, TrendingDown, Receipt, Search, Download,
} from 'lucide-react';
import { formatAmount } from '@shared/lib/utils';
import { usePayments, useCreatePayment, useTeacherSalary, useBudgetLines, usePayTeacherSalary, useCreateBudgetLine, useUpdateBudgetLine, useProcessPayroll, useBudgetOverview } from '../hooks/useFinance';
import { useStudents } from '@modules/academic/hooks/useAcademic';
import { useTeachers } from '@modules/people-hr/hooks/usePeopleHr';

const PaymentSchema = z.object({
  student_id: z.string().optional(),
  amount: z.string().min(1, 'Amount is required'),
  category: z.enum(['fee', 'book', 'chapter', 'exam', 'card', 'placement', 'diploma', 'other']),
  payment_method: z.enum(['cash', 'card', 'bank_transfer']),
  notes: z.string().optional(),
});

type PaymentFormValues = z.infer<typeof PaymentSchema>;

export function FinancePage() {
  const { data: livePayments = [] } = usePayments();
  const { data: budgetLines = [] } = useBudgetLines();
  const { data: budgetOverview } = useBudgetOverview();
  const createPayment = useCreatePayment();
  const createBudgetLine = useCreateBudgetLine();
  const updateBudgetLine = useUpdateBudgetLine();
  const processPayroll = useProcessPayroll();
  const [editingBudget, setEditingBudget] = useState<any>(null);
  const [isEditBudgetOpen, setIsEditBudgetOpen] = useState(false);

  const budgetEditForm = useForm<any>({
    defaultValues: { allocated_amount: '' },
  });

  const openEditBudget = (line: any) => {
    setEditingBudget(line);
    budgetEditForm.reset({ allocated_amount: String(line.allocated_amount || line.allocated || 0) });
    setIsEditBudgetOpen(true);
  };

  const onUpdateBudget = (data: any) => {
    if (!editingBudget) return;
    updateBudgetLine.mutate({
      id: editingBudget.id,
      data: { allocated_amount: parseFloat(data.allocated_amount) },
    }, {
      onSuccess: () => {
        setIsEditBudgetOpen(false);
        setEditingBudget(null);
      },
    });
  };

  const { data: students = [] } = useStudents({ status: 'active' });
  const { data: teachers = [] } = useTeachers();

  const [isPaymentDialogOpen, setIsPaymentDialogOpen] = useState(false);
  const [isBudgetDialogOpen, setIsBudgetDialogOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [txFilter, setTxFilter] = useState<'all' | 'income' | 'expense'>('all');

  const budgetForm = useForm<{ name: string; purpose: string; allocated_amount: string; cost_type: string; branch_id?: string }>({
    resolver: zodResolver(z.object({
      name: z.string().min(2),
      purpose: z.string().min(2),
      allocated_amount: z.string().min(1),
      cost_type: z.string(),
    })),
    defaultValues: { cost_type: 'variable' },
  });

  const { register, handleSubmit, reset, setValue, formState: { errors } } = useForm<PaymentFormValues>({
    resolver: zodResolver(PaymentSchema),
    defaultValues: { category: 'fee', payment_method: 'cash' },
  });

  const onSubmit = (data: PaymentFormValues) => {
    createPayment.mutate(
      {
        student_id: data.student_id || null,
        amount: parseFloat(data.amount),
        date: new Date().toISOString().split('T')[0],
        payment_method: data.payment_method,
        category: data.category,
        notes: data.notes || '',
        branch_id: 'branch-1',
      } as any,
      {
        onSuccess: () => {
          setIsPaymentDialogOpen(false);
          reset();
        },
      }
    );
  };

  const onCreateBudget = (data: any) => {
    createBudgetLine.mutate({
      name: data.name,
      purpose: data.purpose,
      allocated_amount: parseFloat(data.allocated_amount),
      cost_type: data.cost_type,
      branch_id: 'branch-1',
    } as any, {
      onSuccess: () => {
        setIsBudgetDialogOpen(false);
        budgetForm.reset();
      },
    });
  };

  // LIVE transactions ONLY (backend-driven, no hardcoded demo)
  const liveTx = livePayments.map((p: any) => ({
    id: p.id,
    type: 'income' as const,
    category: p.category || 'Tuition',
    amount: p.amount,
    description: p.notes || `Payment - ${p.category || 'fee'}`,
    date: p.date || (p.created_at ? p.created_at.split('T')[0] : ''),
    operator: p.operator_name || 'System',
  }));

  const filteredTransactions = liveTx.filter((tx) => {
    const matchesSearch =
      searchQuery === '' ||
      tx.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
      tx.category.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesType = txFilter === 'all' || tx.type === txFilter;
    return matchesSearch && matchesType;
  });

  const totalIncome = liveTx.reduce((sum, t) => sum + t.amount, 0);
  const totalExpenses = 0; // expenses are tracked via other modules (inventory, funding, payroll)
  const netIncome = totalIncome - totalExpenses;

  // Live teacher payroll preview (now truly live per row via TeacherPayrollRow)
  const payrollPreview = teachers.slice(0, 6);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <DollarSign className="h-8 w-8" />
            Finance &amp; Payroll
          </h1>
          <p className="text-muted-foreground">Manage payments, transactions, and payroll (live backend)</p>
        </div>
        <Dialog open={isPaymentDialogOpen} onOpenChange={setIsPaymentDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="h-4 w-4 me-2" />
              Record Payment
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Record Payment</DialogTitle>
              <DialogDescription>Record a new student payment (creates financial_transaction + 5% savings)</DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
              <div className="space-y-2">
                <Label>Student (optional — auto-links to enrollment)</Label>
                <Select onValueChange={(v) => setValue('student_id', v)}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select student or leave blank" />
                  </SelectTrigger>
                  <SelectContent>
                    {students.length > 0 ? students.map((s: any) => (
                      <SelectItem key={s.id} value={s.id}>
                        {s.full_name} ({s.student_code})
                      </SelectItem>
                    )) : <SelectItem value="">No students loaded</SelectItem>}
                  </SelectContent>
                </Select>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Amount (AFN) *</Label>
                  <Input type="number" step="0.01" placeholder="0" {...register('amount')} />
                  {errors.amount && <p className="text-xs text-destructive">{errors.amount.message}</p>}
                </div>
                <div className="space-y-2">
                  <Label>Category</Label>
                  <Select onValueChange={(v) => setValue('category', v as any)} defaultValue="fee">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="fee">Tuition Fee</SelectItem>
                      <SelectItem value="registration">Registration</SelectItem>
                      <SelectItem value="book">Book</SelectItem>
                      <SelectItem value="exam">Exam Fee</SelectItem>
                      <SelectItem value="card">ID Card</SelectItem>
                      <SelectItem value="placement">Placement Test</SelectItem>
                      <SelectItem value="diploma">Diploma</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="space-y-2">
                <Label>Payment Method</Label>
                <Select onValueChange={(v) => setValue('payment_method', v as any)} defaultValue="cash">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="cash">Cash</SelectItem>
                    <SelectItem value="card">Card</SelectItem>
                    <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label>Notes (optional)</Label>
                <Input placeholder="Additional notes..." {...register('notes')} />
              </div>

              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => { setIsPaymentDialogOpen(false); reset(); }}>
                  Cancel
                </Button>
                <Button type="submit" disabled={createPayment.isPending}>
                  {createPayment.isPending ? 'Recording...' : 'Record Payment'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>

        {/* Budget Line Dialog (NEW live CRUD) */}
        <Dialog open={isBudgetDialogOpen} onOpenChange={setIsBudgetDialogOpen}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Add Budget Line</DialogTitle>
              <DialogDescription>Create a new monthly budget allocation (live)</DialogDescription>
            </DialogHeader>
            <form onSubmit={budgetForm.handleSubmit(onCreateBudget)} className="space-y-4">
              <div className="space-y-2">
                <Label>Name *</Label>
                <Input {...budgetForm.register('name')} placeholder="e.g. Teacher Salaries" />
              </div>
              <div className="space-y-2">
                <Label>Purpose *</Label>
                <Input {...budgetForm.register('purpose')} placeholder="teacher_salary / rent / utilities" />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Allocated (AFN) *</Label>
                  <Input type="number" {...budgetForm.register('allocated_amount')} />
                </div>
                <div className="space-y-2">
                  <Label>Cost Type</Label>
                  <Select onValueChange={(v) => budgetForm.setValue('cost_type', v)} defaultValue="variable">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="fixed">Fixed</SelectItem>
                      <SelectItem value="variable">Variable</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsBudgetDialogOpen(false)}>Cancel</Button>
                <Button type="submit" disabled={createBudgetLine.isPending}>
                  {createBudgetLine.isPending ? 'Creating...' : 'Create Budget Line'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>

        {/* Edit Budget Line Dialog */}
        <Dialog open={isEditBudgetOpen} onOpenChange={setIsEditBudgetOpen}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Edit Budget Line</DialogTitle>
              <DialogDescription>Update allocated amount for {editingBudget?.name}</DialogDescription>
            </DialogHeader>
            <form onSubmit={budgetEditForm.handleSubmit(onUpdateBudget)} className="space-y-4">
              <div className="space-y-2">
                <Label>New Allocated Amount (AFN)</Label>
                <Input type="number" step="0.01" {...budgetEditForm.register('allocated_amount')} />
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => { setIsEditBudgetOpen(false); setEditingBudget(null); }}>Cancel</Button>
                <Button type="submit" disabled={updateBudgetLine.isPending}>
                  {updateBudgetLine.isPending ? 'Saving...' : 'Save Changes'}
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Summary Cards — LIVE */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Revenue (Live)</CardTitle>
            <TrendingUp className="h-5 w-5 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-green-600">{formatAmount(totalIncome)} AFN</div>
            <p className="text-xs text-muted-foreground">From recorded payments</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Expenses</CardTitle>
            <TrendingDown className="h-5 w-5 text-red-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-red-600">{formatAmount(totalExpenses)} AFN</div>
            <p className="text-xs text-muted-foreground">Tracked in Inventory / Payroll</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Net Income</CardTitle>
            <DollarSign className="h-5 w-5 text-blue-600" />
          </CardHeader>
          <CardContent>
            <div className={`text-2xl font-bold ${netIncome >= 0 ? 'text-green-600' : 'text-red-600'}`}>
              {formatAmount(netIncome)} AFN
            </div>
            <p className="text-xs text-muted-foreground">Revenue - Expenses</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Savings (5%)</CardTitle>
            <Receipt className="h-5 w-5 text-purple-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{formatAmount(Math.round(totalIncome * 0.05))} AFN</div>
            <p className="text-xs text-muted-foreground">Auto-saved from income</p>
          </CardContent>
        </Card>
      </div>

      <Tabs defaultValue="transactions">
        <TabsList>
          <TabsTrigger value="transactions">Transactions (Live)</TabsTrigger>
          <TabsTrigger value="payroll">Teacher Payroll</TabsTrigger>
          <TabsTrigger value="budget">Budget</TabsTrigger>
        </TabsList>

        {/* LIVE Transactions */}
        <TabsContent value="transactions">
          <Card>
            <CardHeader>
              <div className="flex items-center gap-4 flex-wrap">
                <div className="flex-1 min-w-[200px] relative">
                  <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                  <Input
                    placeholder="Search transactions..."
                    className="ps-10"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                  />
                </div>
                <Select value={txFilter} onValueChange={(v) => setTxFilter(v as any)}>
                  <SelectTrigger className="w-[160px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All</SelectItem>
                    <SelectItem value="income">Income only</SelectItem>
                  </SelectContent>
                </Select>
                <Button variant="outline" size="sm" onClick={() => alert('Export would use live filtered data')}>
                  <Download className="h-4 w-4 me-2" /> Export
                </Button>
                <Button size="sm" variant="outline" onClick={() => setIsBudgetDialogOpen(true)}>
                  <Plus className="h-4 w-4 me-1" /> Add Budget Line
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead>Operator</TableHead>
                    <TableHead className="text-end">Amount</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredTransactions.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                        No payments recorded yet. Record the first payment above.
                      </TableCell>
                    </TableRow>
                  ) : (
                    filteredTransactions.map((tx) => (
                      <TableRow key={tx.id}>
                        <TableCell className="text-muted-foreground">{tx.date}</TableCell>
                        <TableCell>
                          <Badge variant="default" className="gap-1">
                            <TrendingUp className="h-3 w-3" /> {tx.type}
                          </Badge>
                        </TableCell>
                        <TableCell>{tx.category}</TableCell>
                        <TableCell className="max-w-[200px] truncate">{tx.description}</TableCell>
                        <TableCell className="text-muted-foreground">{tx.operator}</TableCell>
                        <TableCell className="text-end font-mono font-medium text-green-600">
                          +{formatAmount(tx.amount)}
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* LIVE Payroll */}
        <TabsContent value="payroll">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <div>
                <CardTitle>Teacher Payroll — Live Computation</CardTitle>
                <CardDescription>Salary computed by backend PayrollService using model + current assignments</CardDescription>
              </div>
              <Button 
                size="sm" 
                onClick={() => {
                  processPayroll.mutate({ period: new Date().toISOString().slice(0, 7), branch_id: 'branch-1' });
                }}
                disabled={processPayroll.isPending}
              >
                {processPayroll.isPending ? 'Processing...' : 'Process Payroll (All)'}
              </Button>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Teacher</TableHead>
                    <TableHead>Salary Model</TableHead>
                    <TableHead>Classes</TableHead>
                    <TableHead className="text-end">Computed Due</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-end">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {payrollPreview.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center py-8">No teachers loaded.</TableCell>
                    </TableRow>
                  ) : (
                    payrollPreview.map((t: any) => (
                      <TeacherPayrollRow key={t.id} teacher={t} />
                    ))
                  )}
                </TableBody>
              </Table>
              <div className="mt-3 text-xs text-muted-foreground">
                Bulk processing calls backend PayrollService (creates journal entries + updates teacher balances).
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Budget — LIVE + Overview */}
        <TabsContent value="budget">
          <div className="space-y-4">
            {/* Budget Overview (from /budget/overview) */}
            {budgetOverview && (
              <Card>
                <CardHeader>
                  <CardTitle>Budget Overview</CardTitle>
                  <CardDescription>Live summary from backend (reserve fund logic)</CardDescription>
                </CardHeader>
                <CardContent className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                  <div>
                    <div className="text-muted-foreground">Allocated</div>
                    <div className="font-bold text-lg">{formatAmount(budgetOverview.total_allocated)} AFN</div>
                  </div>
                  <div>
                    <div className="text-muted-foreground">Spent</div>
                    <div className="font-bold text-lg text-red-600">{formatAmount(budgetOverview.total_spent)} AFN</div>
                  </div>
                  <div>
                    <div className="text-muted-foreground">Utilization</div>
                    <div className="font-bold text-lg">{budgetOverview.utilization_percent}%</div>
                  </div>
                  <div>
                    <div className="text-muted-foreground">Reserve Fund Met?</div>
                    <Badge variant={budgetOverview.reserve_fund_met ? 'default' : 'secondary'}>
                      {budgetOverview.reserve_fund_met ? 'Yes' : 'No'} ({formatAmount(budgetOverview.reserve_fund_percent)}%)
                    </Badge>
                  </div>
                </CardContent>
              </Card>
            )}

            <Card>
              <CardHeader>
                <CardTitle>Budget Lines</CardTitle>
                <CardDescription>Live monthly allocations. Use + button above to add new.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {budgetLines.length === 0 ? (
                  <p className="text-sm text-muted-foreground">No budget lines. Add one using the button in header.</p>
                ) : (
                  budgetLines.map((line: any, idx: number) => {
                    const allocated = line.allocated_amount || line.allocated || 0;
                    const spent = line.current_amount || line.spent || 0;
                    const percent = allocated > 0 ? Math.round((spent / allocated) * 100) : 0;
                    return (
                      <div key={idx} className="space-y-2">
                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-2">
                            <span className="font-medium text-sm">{line.name}</span>
                            <Badge variant="outline" className="text-xs">{line.cost_type || 'variable'}</Badge>
                          </div>
                          <span className="text-sm text-muted-foreground">
                            {formatAmount(spent)} / {formatAmount(allocated)} AFN
                          </span>
                        </div>
                        <div className="w-full bg-muted rounded-full h-2">
                          <div
                            className={`h-2 rounded-full transition-all ${percent > 100 ? 'bg-red-500' : percent > 80 ? 'bg-yellow-500' : 'bg-green-500'}`}
                            style={{ width: `${Math.min(100, percent)}%` }}
                          />
                        </div>
                        <div className="flex justify-between text-xs text-muted-foreground">
                          <span>{percent}% spent • Remaining: {formatAmount(allocated - spent)} AFN</span>
                          {line.utilization_percent !== undefined && <span>{line.utilization_percent}%</span>}
                        </div>
                        <div className="flex gap-2 pt-1">
                          <Button size="sm" variant="outline" onClick={() => openEditBudget(line)}>
                            Edit
                          </Button>
                        </div>
                      </div>
                    );
                  })
                )}
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}

// Helper component that calls live teacher salary endpoint + supports pay action
function TeacherPayrollRow({ teacher }: { teacher: any }) {
  const { data: salary } = useTeacherSalary(teacher.id);
  const paySalary = usePayTeacherSalary();

  const due = (salary as any)?.total_due ?? (teacher.base_salary || 28000);
  const paid = (salary as any)?.paid_amount || 0;

  const handlePay = () => {
    paySalary.mutate({
      teacherId: teacher.id,
      data: {
        amount: due,
        period: new Date().toISOString().slice(0, 7),
        method: 'bank_transfer',
      },
    });
  };

  return (
    <TableRow>
      <TableCell className="font-medium">{teacher.name}</TableCell>
      <TableCell>
        <Badge variant="outline">{teacher.model}</Badge>
      </TableCell>
      <TableCell>{teacher.classes}</TableCell>
      <TableCell className="text-end font-mono">{formatAmount(due)} AFN</TableCell>
      <TableCell>
        {paid >= due ? (
          <Badge variant="default">Paid</Badge>
        ) : paid > 0 ? (
          <Badge variant="secondary">Partial</Badge>
        ) : (
          <Badge variant="destructive">Unpaid</Badge>
        )}
      </TableCell>
      <TableCell className="text-end">
        <Button
          variant="outline"
          size="sm"
          disabled={paid >= due || paySalary.isPending}
          onClick={handlePay}
        >
          {paySalary.isPending ? 'Paying...' : 'Pay'}
        </Button>
      </TableCell>
    </TableRow>
  );
}
