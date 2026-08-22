/**
 * Finance Page — Finance & Payroll Module
 * Payment recording, transaction history, budget overview, payroll
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
  DollarSign, Plus, TrendingUp, TrendingDown, Receipt, CreditCard,
  ArrowUpRight, ArrowDownRight, Search, Download,
} from 'lucide-react';
import { formatAmount } from '@shared/lib/utils';

const PaymentSchema = z.object({
  student_name: z.string().min(1, 'Student is required'),
  amount: z.string().min(1, 'Amount is required'),
  category: z.enum(['fee', 'book', 'chapter', 'exam', 'card', 'placement', 'diploma', 'other']),
  payment_method: z.enum(['cash', 'card', 'bank_transfer']),
  notes: z.string().optional(),
});

type PaymentFormValues = z.infer<typeof PaymentSchema>;

interface Transaction {
  id: string;
  type: 'income' | 'expense';
  category: string;
  amount: number;
  description: string;
  date: string;
  operator: string;
}

const transactions: Transaction[] = [
  { id: '1', type: 'income', category: 'Tuition', amount: 15000, description: 'Ahmad Rahimi — Semester fee', date: '2026-08-22', operator: 'Reception' },
  { id: '2', type: 'income', category: 'Registration', amount: 5000, description: 'Zahra Noori — New student registration', date: '2026-08-22', operator: 'Reception' },
  { id: '3', type: 'expense', category: 'Rent', amount: 50000, description: 'Monthly office rent', date: '2026-08-21', operator: 'Finance' },
  { id: '4', type: 'income', category: 'Book Sale', amount: 3500, description: 'Fatima Ahmadi — TOEFL Guide + Reading', date: '2026-08-21', operator: 'Reception' },
  { id: '5', type: 'income', category: 'Exam Fee', amount: 2000, description: 'Mohammad Karimi — Mock exam', date: '2026-08-20', operator: 'Reception' },
  { id: '6', type: 'expense', category: 'Payroll', amount: 45000, description: 'Teacher salaries — August', date: '2026-08-20', operator: 'Finance' },
  { id: '7', type: 'income', category: 'Tuition', amount: 7000, description: 'Sara Mohammadi — TOEFL Prep fee', date: '2026-08-19', operator: 'Reception' },
  { id: '8', type: 'expense', category: 'Supplies', amount: 3200, description: 'Office supplies & printing', date: '2026-08-19', operator: 'Admin' },
  { id: '9', type: 'income', category: 'Card Fee', amount: 2000, description: 'ID cards — 10 students', date: '2026-08-18', operator: 'Reception' },
  { id: '10', type: 'income', category: 'Placement', amount: 900, description: 'Placement test fees — 3 visitors', date: '2026-08-18', operator: 'Reception' },
];

const budgetLines = [
  { name: 'Teacher Salaries', allocated: 200000, spent: 145000, type: 'fixed' },
  { name: 'Office Rent', allocated: 50000, spent: 50000, type: 'fixed' },
  { name: 'Utilities', allocated: 15000, spent: 12000, type: 'variable' },
  { name: 'Supplies', allocated: 10000, spent: 7200, type: 'variable' },
  { name: 'Marketing', allocated: 20000, spent: 8500, type: 'variable' },
  { name: 'Maintenance', allocated: 10000, spent: 3000, type: 'variable' },
];

export function FinancePage() {
  const [isPaymentDialogOpen, setIsPaymentDialogOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [txFilter, setTxFilter] = useState<'all' | 'income' | 'expense'>('all');

  const { register, handleSubmit, reset, formState: { errors } } = useForm<PaymentFormValues>({
    resolver: zodResolver(PaymentSchema),
    defaultValues: { category: 'fee', payment_method: 'cash' },
  });

  const onSubmit = (data: PaymentFormValues) => {
    // In production: call FinancePayroll's PaymentService
    setIsPaymentDialogOpen(false);
    reset();
  };

  const filteredTransactions = transactions.filter((tx) => {
    const matchesSearch = searchQuery === '' ||
      tx.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
      tx.category.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesType = txFilter === 'all' || tx.type === txFilter;
    return matchesSearch && matchesType;
  });

  const totalIncome = transactions.filter((t) => t.type === 'income').reduce((sum, t) => sum + t.amount, 0);
  const totalExpenses = transactions.filter((t) => t.type === 'expense').reduce((sum, t) => sum + t.amount, 0);
  const netIncome = totalIncome - totalExpenses;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <DollarSign className="h-8 w-8" />
            Finance & Payroll
          </h1>
          <p className="text-muted-foreground">Manage payments, transactions, and budget</p>
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
              <DialogDescription>Record a new payment from a student</DialogDescription>
            </DialogHeader>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
              <div className="space-y-2">
                <Label>Student Name</Label>
                <Input placeholder="Search student..." {...register('student_name')} />
                {errors.student_name && <p className="text-xs text-destructive">{errors.student_name.message}</p>}
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Amount (AFN)</Label>
                  <Input type="number" placeholder="0" {...register('amount')} />
                  {errors.amount && <p className="text-xs text-destructive">{errors.amount.message}</p>}
                </div>
                <div className="space-y-2">
                  <Label>Category</Label>
                  <Select defaultValue="fee" onValueChange={(v) => register('category').onChange({ target: { value: v } })}>
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
                <Select defaultValue="cash" onValueChange={(v) => register('payment_method').onChange({ target: { value: v } })}>
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
                <Button type="button" variant="outline" onClick={() => { setIsPaymentDialogOpen(false); reset(); }}>Cancel</Button>
                <Button type="submit">Record Payment</Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Summary Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Revenue</CardTitle>
            <TrendingUp className="h-5 w-5 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-green-600">{formatAmount(totalIncome)} AFN</div>
            <p className="text-xs text-muted-foreground">This period</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Expenses</CardTitle>
            <TrendingDown className="h-5 w-5 text-red-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-red-600">{formatAmount(totalExpenses)} AFN</div>
            <p className="text-xs text-muted-foreground">This period</p>
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
          <TabsTrigger value="transactions">Transactions</TabsTrigger>
          <TabsTrigger value="budget">Budget</TabsTrigger>
          <TabsTrigger value="payroll">Payroll</TabsTrigger>
        </TabsList>

        {/* Transactions Tab */}
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
                <Select value={txFilter} onValueChange={(v) => setTxFilter(v as 'all' | 'income' | 'expense')}>
                  <SelectTrigger className="w-[160px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All</SelectItem>
                    <SelectItem value="income">Income only</SelectItem>
                    <SelectItem value="expense">Expenses only</SelectItem>
                  </SelectContent>
                </Select>
                <Button variant="outline" size="sm">
                  <Download className="h-4 w-4 me-2" /> Export
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
                  {filteredTransactions.map((tx) => (
                    <TableRow key={tx.id}>
                      <TableCell className="text-muted-foreground">{tx.date}</TableCell>
                      <TableCell>
                        <Badge variant={tx.type === 'income' ? 'default' : 'destructive'} className="gap-1">
                          {tx.type === 'income' ? <ArrowUpRight className="h-3 w-3" /> : <ArrowDownRight className="h-3 w-3" />}
                          {tx.type}
                        </Badge>
                      </TableCell>
                      <TableCell>{tx.category}</TableCell>
                      <TableCell className="max-w-[200px] truncate">{tx.description}</TableCell>
                      <TableCell className="text-muted-foreground">{tx.operator}</TableCell>
                      <TableCell className={`text-end font-mono font-medium ${tx.type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                        {tx.type === 'income' ? '+' : '-'}{formatAmount(tx.amount)}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Budget Tab */}
        <TabsContent value="budget">
          <Card>
            <CardHeader>
              <CardTitle>Budget Lines</CardTitle>
              <CardDescription>Monthly budget allocation and spending</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {budgetLines.map((line) => {
                const percent = Math.round((line.spent / line.allocated) * 100);
                const isOverBudget = percent > 100;
                return (
                  <div key={line.name} className="space-y-2">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <span className="font-medium text-sm">{line.name}</span>
                        <Badge variant="outline" className="text-xs">{line.type}</Badge>
                      </div>
                      <span className="text-sm text-muted-foreground">
                        {formatAmount(line.spent)} / {formatAmount(line.allocated)} AFN
                      </span>
                    </div>
                    <div className="w-full bg-muted rounded-full h-2">
                      <div
                        className={`h-2 rounded-full transition-all ${isOverBudget ? 'bg-red-500' : percent > 80 ? 'bg-yellow-500' : 'bg-green-500'}`}
                        style={{ width: `${Math.min(100, percent)}%` }}
                      />
                    </div>
                    <div className="flex justify-between text-xs text-muted-foreground">
                      <span>{percent}% spent</span>
                      <span>{formatAmount(line.allocated - line.spent)} AFN remaining</span>
                    </div>
                  </div>
                );
              })}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Payroll Tab */}
        <TabsContent value="payroll">
          <Card>
            <CardHeader>
              <CardTitle>Teacher Payroll — August 2026</CardTitle>
              <CardDescription>Salary computation and disbursement</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Teacher</TableHead>
                    <TableHead>Salary Model</TableHead>
                    <TableHead>Classes</TableHead>
                    <TableHead className="text-end">Due Amount</TableHead>
                    <TableHead className="text-end">Paid</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-end">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {[
                    { name: 'Mr. Ahmed Karimi', model: 'hybrid', classes: 3, due: 35000, paid: 0 },
                    { name: 'Ms. Sarah Noori', model: 'per_skill', classes: 4, due: 28000, paid: 28000 },
                    { name: 'Mr. Karim Rahimi', model: 'fixed', classes: 2, due: 25000, paid: 25000 },
                    { name: 'Ms. Fatima Ahmadi', model: 'per_session', classes: 5, due: 32000, paid: 16000 },
                    { name: 'Mr. Ali Hussaini', model: 'per_level', classes: 3, due: 22000, paid: 0 },
                  ].map((teacher, i) => (
                    <TableRow key={i}>
                      <TableCell className="font-medium">{teacher.name}</TableCell>
                      <TableCell>
                        <Badge variant="outline">{teacher.model}</Badge>
                      </TableCell>
                      <TableCell>{teacher.classes}</TableCell>
                      <TableCell className="text-end font-mono">{formatAmount(teacher.due)} AFN</TableCell>
                      <TableCell className="text-end font-mono">{formatAmount(teacher.paid)} AFN</TableCell>
                      <TableCell>
                        {teacher.paid >= teacher.due ? (
                          <Badge variant="default">Paid</Badge>
                        ) : teacher.paid > 0 ? (
                          <Badge variant="secondary">Partial</Badge>
                        ) : (
                          <Badge variant="destructive">Unpaid</Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-end">
                        <Button variant="outline" size="sm" disabled={teacher.paid >= teacher.due}>
                          Pay
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
