/**
 * Finance Page — Finance & Payroll Module
 */

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { DollarSign, Plus, TrendingUp, TrendingDown, Receipt, CreditCard } from 'lucide-react';
import { formatAmount } from '@shared/lib/utils';

const summaryCards = [
  { title: 'Total Revenue', value: 245000, icon: TrendingUp, color: 'text-green-600' },
  { title: 'Total Expenses', value: 180000, icon: TrendingDown, color: 'text-red-600' },
  { title: 'Net Income', value: 65000, icon: DollarSign, color: 'text-blue-600' },
  { title: 'Pending Payments', value: 32000, icon: Receipt, color: 'text-orange-600' },
];

const recentTransactions = [
  { id: '1', type: 'income', category: 'Tuition', amount: 15000, student: 'Ahmad Rahimi', date: '2026-08-20' },
  { id: '2', type: 'expense', category: 'Rent', amount: 50000, student: null, date: '2026-08-19' },
  { id: '3', type: 'income', category: 'Book Sale', amount: 3500, student: 'Fatima Ahmadi', date: '2026-08-19' },
  { id: '4', type: 'income', category: 'Registration', amount: 5000, student: 'Zahra Noori', date: '2026-08-18' },
  { id: '5', type: 'expense', category: 'Teacher Salary', amount: 25000, student: null, date: '2026-08-18' },
];

export function FinancePage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <DollarSign className="h-8 w-8" />
            Finance & Payroll
          </h1>
          <p className="text-muted-foreground">Manage payments, invoices, and payroll</p>
        </div>
        <Button>
          <Plus className="h-4 w-4 me-2" />
          Record Payment
        </Button>
      </div>

      {/* Summary Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {summaryCards.map((card) => {
          const Icon = card.icon;
          return (
            <Card key={card.title}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{card.title}</CardTitle>
                <Icon className={`h-5 w-5 ${card.color}`} />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{formatAmount(card.value)} AFN</div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      {/* Recent Transactions */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Transactions</CardTitle>
          <CardDescription>Latest financial activity</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {recentTransactions.map((tx) => (
              <div key={tx.id} className="flex items-center justify-between py-2 border-b last:border-0">
                <div className="flex items-center gap-3">
                  <div className={`p-2 rounded-full ${tx.type === 'income' ? 'bg-green-100' : 'bg-red-100'}`}>
                    {tx.type === 'income' ? (
                      <TrendingUp className="h-4 w-4 text-green-600" />
                    ) : (
                      <TrendingDown className="h-4 w-4 text-red-600" />
                    )}
                  </div>
                  <div>
                    <p className="font-medium">{tx.category}</p>
                    <p className="text-sm text-muted-foreground">
                      {tx.student || 'General'} • {tx.date}
                    </p>
                  </div>
                </div>
                <div className={`font-semibold ${tx.type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                  {tx.type === 'income' ? '+' : '-'}{formatAmount(tx.amount)} AFN
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
